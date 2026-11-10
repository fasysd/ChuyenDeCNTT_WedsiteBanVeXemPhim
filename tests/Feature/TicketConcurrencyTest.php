<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\TicketDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TicketConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Movie $movie;
    private Room $room;
    private Seat $seat1;
    private Seat $seat2;
    private Showtime $showtime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::create([
            'username' => 'user_a',
            'email' => 'user_a@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        $this->userB = User::create([
            'username' => 'user_b',
            'email' => 'user_b@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        $this->movie = Movie::create([
            'title' => 'Inception Concurrency',
            'duration' => 148,
            'release_date' => now()->subDay(),
        ]);

        $this->room = Room::create([
            'name' => 'Cinema Room 1',
            'total_seats' => 10,
        ]);

        $this->seat1 = Seat::create([
            'room_id' => $this->room->id,
            'seat_row' => 'A',
            'seat_number' => 1,
            'type' => 'STANDARD',
        ]);

        $this->seat2 = Seat::create([
            'room_id' => $this->room->id,
            'seat_row' => 'A',
            'seat_number' => 2,
            'type' => 'VIP',
        ]);

        $this->showtime = Showtime::create([
            'movie_id' => $this->movie->id,
            'room_id' => $this->room->id,
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(5),
            'price_standard' => 100000,
        ]);
    }

    /**
     * Kịch bản 1: User A đã đặt vé ghế 1 -> User B cố gắng đặt cùng ghế 1 sẽ bị chặn an toàn
     */
    public function test_concurrent_booking_second_user_fails_cleanly(): void
    {
        // User A đặt ghế 1 thành công
        $this->actingAs($this->userA);
        $responseA = $this->post(route('ticket.store', $this->movie->id), [
            'showtime_id' => $this->showtime->id,
            'seat_ids' => [$this->seat1->id],
        ]);
        $responseA->assertRedirect(route('user.account.tickets'));
        $responseA->assertSessionHas('success');

        // User B gửi request đặt cùng ghế 1
        $this->actingAs($this->userB);
        $responseB = $this->post(route('ticket.store', $this->movie->id), [
            'showtime_id' => $this->showtime->id,
            'seat_ids' => [$this->seat1->id],
        ]);

        // User B phải bị chặn lại với thông báo lỗi thân thiện, không có ngoại lệ 500
        $responseB->assertRedirect(route('ticket.booking', $this->movie->id));
        $responseB->assertSessionHas('error');

        // Database chỉ có đúng 1 vé HOLDING cho ghế 1 thuộc về User A
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->userA->id,
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseCount('ticket_details', 1);
        $this->assertDatabaseHas('ticket_details', [
            'seat_id' => $this->seat1->id,
            'status' => 'HOLDING',
        ]);
    }

    /**
     * Kịch bản 2: Tầng Atomic Cache Lock chặn đứng request khi ghế đang có tiến trình khác chiếm giữ
     */
    public function test_cache_lock_prevents_simultaneous_booking_attempt(): void
    {
        $this->actingAs($this->userB);

        // Giả lập tiến trình khác đang giữ Atomic Cache Lock cho ghế 1
        $lockKey = "lock:showtime:{$this->showtime->id}:seat:{$this->seat1->id}";
        $externalLock = Cache::lock($lockKey, 10);
        $this->assertTrue($externalLock->get(), 'Không thể thiết lập lock test.');

        try {
            // User B gửi request đặt ghế 1 khi lock đang bị giữ
            $response = $this->post(route('ticket.store', $this->movie->id), [
                'showtime_id' => $this->showtime->id,
                'seat_ids' => [$this->seat1->id],
            ]);

            // Request bị từ chối ngay từ tầng Cache (Fail-Fast) mà không tạo booking hay ticket nào
            $response->assertRedirect(route('ticket.booking', $this->movie->id));
            $response->assertSessionHas('error');
            $this->assertDatabaseCount('bookings', 0);
            $this->assertDatabaseCount('ticket_details', 0);
        } finally {
            $externalLock->release();
        }
    }

    /**
     * Kịch bản 3: Tái sử dụng ghế sau khi đơn cũ bị hủy (Fix lỗi Unique Constraint)
     */
    public function test_can_rebook_seat_after_previous_booking_was_cancelled(): void
    {
        // 1. User A đặt ghế 1
        $this->actingAs($this->userA);
        $this->post(route('ticket.store', $this->movie->id), [
            'showtime_id' => $this->showtime->id,
            'seat_ids' => [$this->seat1->id],
        ]);

        $bookingA = Booking::where('user_id', $this->userA->id)->first();
        $this->assertNotNull($bookingA);

        // 2. User A hủy đơn
        $this->post(route('user.account.bookings.cancel', $bookingA->id));
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingA->id,
            'status' => 'CANCELLED',
        ]);
        $this->assertDatabaseHas('ticket_details', [
            'booking_id' => $bookingA->id,
            'seat_id' => $this->seat1->id,
            'status' => 'CANCELLED',
        ]);

        // 3. User B vào đặt lại chính chiếc ghế 1 vừa bị hủy
        $this->actingAs($this->userB);
        $responseB = $this->post(route('ticket.store', $this->movie->id), [
            'showtime_id' => $this->showtime->id,
            'seat_ids' => [$this->seat1->id],
        ]);

        // Đặt vé phải thành công mỹ mãn, không bị vi phạm Unique Constraint
        $responseB->assertRedirect(route('user.account.tickets'));
        $responseB->assertSessionHas('success');

        $bookingB = Booking::where('user_id', $this->userB->id)->first();
        $this->assertNotNull($bookingB);
        $this->assertEquals('PENDING', $bookingB->status);

        // Vé ghế 1 giờ thuộc về booking của User B với trạng thái HOLDING
        $this->assertDatabaseHas('ticket_details', [
            'booking_id' => $bookingB->id,
            'seat_id' => $this->seat1->id,
            'status' => 'HOLDING',
        ]);
    }

    /**
     * Kịch bản 4: Tự động giải phóng ghế khi quá thời gian giữ chỗ (10 phút)
     */
    public function test_expired_holding_seats_are_automatically_released(): void
    {
        // User A tạo đơn giữ chỗ nhưng đã hết hạn (giả lập 15 phút trước)
        $expiredBooking = Booking::create([
            'user_id' => $this->userA->id,
            'total_price' => 100000,
            'status' => 'PENDING',
            'holding_expires_at' => now()->subMinutes(15),
            'expired_at' => now()->subMinutes(15),
        ]);

        TicketDetail::create([
            'booking_id' => $expiredBooking->id,
            'showtime_id' => $this->showtime->id,
            'seat_id' => $this->seat1->id,
            'final_price' => 100000,
            'status' => 'HOLDING',
        ]);

        // User B truy cập trang chọn ghế -> Hệ thống tự động quét và giải phóng đơn quá hạn
        $this->actingAs($this->userB);
        $responseBooking = $this->get(route('ticket.booking', [
            'movie' => $this->movie->id,
            'showtime' => $this->showtime->id,
        ]));
        $responseBooking->assertStatus(200);

        // Đơn của User A đã tự động đổi sang CANCELLED
        $this->assertDatabaseHas('bookings', [
            'id' => $expiredBooking->id,
            'status' => 'CANCELLED',
        ]);

        // User B tiến hành đặt ghế 1 thành công
        $responseStore = $this->post(route('ticket.store', $this->movie->id), [
            'showtime_id' => $this->showtime->id,
            'seat_ids' => [$this->seat1->id],
        ]);

        $responseStore->assertRedirect(route('user.account.tickets'));
        $responseStore->assertSessionHas('success');
    }

    /**
     * Kịch bản 5: Lệnh Artisan giải phóng các đơn giữ chỗ quá hạn
     */
    public function test_artisan_command_releases_expired_holds(): void
    {
        // Tạo đơn quá hạn
        $expiredBooking = Booking::create([
            'user_id' => $this->userA->id,
            'total_price' => 100000,
            'status' => 'PENDING',
            'holding_expires_at' => now()->subMinutes(20),
            'expired_at' => now()->subMinutes(20),
        ]);

        TicketDetail::create([
            'booking_id' => $expiredBooking->id,
            'showtime_id' => $this->showtime->id,
            'seat_id' => $this->seat1->id,
            'final_price' => 100000,
            'status' => 'HOLDING',
        ]);

        // Chạy lệnh artisan
        $exitCode = Artisan::call('tickets:release-expired-holds');
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Đã giải phóng 1 đơn giữ chỗ quá hạn', Artisan::output());

        $this->assertDatabaseHas('bookings', [
            'id' => $expiredBooking->id,
            'status' => 'CANCELLED',
        ]);
        $this->assertDatabaseHas('ticket_details', [
            'id' => $expiredBooking->ticketDetails()->first()->id,
            'status' => 'CANCELLED',
        ]);
    }
}
