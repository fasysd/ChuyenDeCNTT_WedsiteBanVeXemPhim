<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\TicketDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovieTicketBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_movies_and_about_page(): void
    {
        $movie = Movie::create([
            'title' => 'Dune 2',
            'duration' => 166,
            'release_date' => now()->subDay(),
            'genre' => 'Sci-Fi',
        ]);

        $responseHome = $this->get('/movies');
        $responseHome->assertStatus(200);
        $responseHome->assertSee('Dune 2');

        $responseDetail = $this->get('/movies/' . $movie->id);
        $responseDetail->assertStatus(200);
        $responseDetail->assertSee('Dune 2');

        $responseAbout = $this->get('/help/about');
        $responseAbout->assertStatus(200);
    }

    public function test_user_can_register_and_login(): void
    {
        $responseRegister = $this->post('/register', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'full_name' => 'Nguyen Van A',
            'phone' => '0912345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $responseRegister->assertRedirect(route('movies'));
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->assertGuest();

        $responseLogin = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);
        $responseLogin->assertRedirect(route('movies'));
        $this->assertAuthenticated();
    }

    public function test_customer_can_book_seats_with_correct_vip_price(): void
    {
        $customer = User::create([
            'username' => 'customer_test',
            'email' => 'cust@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        $movie = Movie::create([
            'title' => 'Avengers',
            'duration' => 180,
            'release_date' => now()->subDays(5),
        ]);

        $room = Room::create([
            'name' => 'Phòng 1',
            'total_seats' => 2,
        ]);

        $seatStandard = Seat::create([
            'room_id' => $room->id,
            'seat_row' => 'A',
            'seat_number' => 1,
            'type' => 'STANDARD',
        ]);

        $seatVip = Seat::create([
            'room_id' => $room->id,
            'seat_row' => 'E',
            'seat_number' => 1,
            'type' => 'VIP',
        ]);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(4),
            'price_standard' => 100000,
        ]);

        // Đăng nhập khách hàng
        $this->actingAs($customer);

        // Xem màn hình chọn ghế
        $responseBooking = $this->get(route('ticket.booking', $movie->id));
        $responseBooking->assertStatus(200);

        // Đặt 1 ghế thường (100.000) và 1 ghế VIP (120.000) -> Tổng phải là 220.000
        $responseStore = $this->post(route('ticket.store', $movie->id), [
            'showtime_id' => $showtime->id,
            'seat_ids' => [$seatStandard->id, $seatVip->id],
        ]);

        $responseStore->assertRedirect(route('user.account.tickets'));
        $responseStore->assertSessionHas('success');

        // Kiểm tra database: 1 đơn đặt hàng có tổng tiền 220000
        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'total_price' => 220000,
            'status' => 'PENDING',
        ]);

        // Kiểm tra 2 vé được lưu với trạng thái HOLDING và đơn giá riêng biệt
        $this->assertDatabaseHas('ticket_details', [
            'seat_id' => $seatStandard->id,
            'final_price' => 100000,
            'status' => 'HOLDING',
        ]);

        $this->assertDatabaseHas('ticket_details', [
            'seat_id' => $seatVip->id,
            'final_price' => 120000,
            'status' => 'HOLDING',
        ]);

        // Đến trang vé của tôi và kiểm tra danh sách vé
        $responseTicketsPage = $this->get(route('user.account.tickets'));
        $responseTicketsPage->assertStatus(200);
        $responseTicketsPage->assertSee('Chờ thanh toán');

        // Thanh toán vé ghế thường
        $ticket = TicketDetail::where('seat_id', $seatStandard->id)->first();
        $responsePay = $this->post(route('user.account.tickets.payment', $ticket->id));
        $responsePay->assertRedirect(route('user.account.tickets.show', $ticket->id));

        $this->assertDatabaseHas('ticket_details', [
            'id' => $ticket->id,
            'status' => 'BOOKED',
        ]);
    }

    public function test_customer_can_pay_entire_booking_with_payment_record(): void
    {
        $customer = User::create([
            'username' => 'pay_cust',
            'email' => 'pay_cust@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        $movie = Movie::create(['title' => 'Inception', 'duration' => 148]);
        $room = Room::create(['name' => 'Phòng 2', 'total_seats' => 2]);
        $seat1 = Seat::create(['room_id' => $room->id, 'seat_row' => 'B', 'seat_number' => 1, 'type' => 'STANDARD']);
        $seat2 = Seat::create(['room_id' => $room->id, 'seat_row' => 'B', 'seat_number' => 2, 'type' => 'STANDARD']);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addHours(5),
            'end_time' => now()->addHours(8),
            'price_standard' => 90000,
        ]);

        $this->actingAs($customer);

        // Đặt vé 2 ghế
        $this->post(route('ticket.store', $movie->id), [
            'showtime_id' => $showtime->id,
            'seat_ids' => [$seat1->id, $seat2->id],
        ]);

        $booking = Booking::where('user_id', $customer->id)->latest('id')->first();
        $this->assertEquals(180000, $booking->total_price);
        $this->assertEquals('PENDING', $booking->status);

        // Khách hàng thực hiện thanh toán cho toàn bộ đơn đặt vé
        $responsePayBooking = $this->post(route('user.account.bookings.pay', $booking->id), [
            'payment_method' => 'E_WALLET',
        ]);

        $responsePayBooking->assertRedirect(route('user.account.tickets'));
        $responsePayBooking->assertSessionHas('success');

        // Kiểm tra Booking đã sang PAID
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'PAID',
        ]);

        // Kiểm tra bảng payments có bản ghi mới
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'method' => 'E_WALLET',
            'status' => 'PAID',
            'amount' => 180000,
            'provider' => 'MOMO',
        ]);

        // Kiểm tra tất cả các vé của đơn đều chuyển sang BOOKED
        $this->assertEquals(2, $booking->ticketDetails()->where('status', 'BOOKED')->count());
    }

    public function test_customer_can_cancel_pending_booking(): void
    {
        $customer = User::create([
            'username' => 'cancel_cust',
            'email' => 'cancel_cust@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        $movie = Movie::create(['title' => 'Interstellar', 'duration' => 169]);
        $room = Room::create(['name' => 'Phòng 3', 'total_seats' => 1]);
        $seat = Seat::create(['room_id' => $room->id, 'seat_row' => 'C', 'seat_number' => 1, 'type' => 'STANDARD']);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addHours(6),
            'end_time' => now()->addHours(9),
            'price_standard' => 80000,
        ]);

        $this->actingAs($customer);

        // Đặt vé
        $this->post(route('ticket.store', $movie->id), [
            'showtime_id' => $showtime->id,
            'seat_ids' => [$seat->id],
        ]);

        $booking = Booking::where('user_id', $customer->id)->latest('id')->first();
        $this->assertEquals('PENDING', $booking->status);

        // Khách hàng hủy đơn đặt vé
        $responseCancel = $this->post(route('user.account.bookings.cancel', $booking->id));
        $responseCancel->assertRedirect(route('user.account.tickets'));
        $responseCancel->assertSessionHas('success');

        // Kiểm tra trạng thái Booking và TicketDetails đều chuyển thành CANCELLED
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'CANCELLED',
        ]);

        $this->assertDatabaseHas('ticket_details', [
            'booking_id' => $booking->id,
            'status' => 'CANCELLED',
        ]);
    }

    public function test_customer_refund_request_flow_and_admin_approval(): void
    {
        $customer = User::create([
            'username' => 'refund_cust',
            'email' => 'refund_cust@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        $admin = User::create([
            'username' => 'admin_mgr',
            'email' => 'admin_mgr@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
        ]);

        $movie = Movie::create(['title' => 'Oppenheimer', 'duration' => 180]);
        $room = Room::create(['name' => 'Phòng 4', 'total_seats' => 1]);
        $seat = Seat::create(['room_id' => $room->id, 'seat_row' => 'D', 'seat_number' => 1, 'type' => 'STANDARD']);

        // Suất chiếu cách hiện tại 5 tiếng (Thỏa mãn điều kiện >= 2 tiếng)
        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addHours(5),
            'end_time' => now()->addHours(8),
            'price_standard' => 120000,
        ]);

        $booking = Booking::create([
            'user_id' => $customer->id,
            'total_price' => 120000,
            'status' => 'PAID',
        ]);

        TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'final_price' => 120000,
            'status' => 'BOOKED',
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'method' => 'BANK_TRANSFER',
            'status' => 'PAID',
            'amount' => 120000,
            'payment_code' => 'PAY-OPP123',
            'paid_at' => now(),
        ]);

        // 1. Khách gửi yêu cầu hoàn tiền
        $this->actingAs($customer);
        $responseRefund = $this->post(route('user.account.bookings.refund', $booking->id));
        $responseRefund->assertRedirect(route('user.account.tickets'));
        $responseRefund->assertSessionHas('success');

        // Kiểm tra bản ghi refund được tạo ở trạng thái PENDING
        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'status' => 'PENDING',
            'amount' => 120000,
        ]);

        $refund = Refund::where('payment_id', $payment->id)->first();

        // 2. Admin đăng nhập và duyệt hoàn tiền
        $this->actingAs($admin);
        $responseRefundList = $this->get(route('admin.refunds.index'));
        $responseRefundList->assertStatus(200);
        $responseRefundList->assertSee('PAY-OPP123');

        $responseApprove = $this->post(route('admin.refunds.approve', $refund->id));
        $responseApprove->assertSessionHas('success');

        // Kiểm tra sau khi Admin duyệt:
        // - Refund -> COMPLETED
        // - Booking -> REFUNDED
        // - Ticket -> CANCELLED (ghế được giải phóng)
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'COMPLETED',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'REFUNDED',
        ]);

        $this->assertDatabaseHas('ticket_details', [
            'booking_id' => $booking->id,
            'status' => 'CANCELLED',
        ]);
    }

    public function test_customer_cannot_refund_within_2_hours_of_showtime(): void
    {
        $customer = User::create([
            'username' => 'late_cust',
            'email' => 'late_cust@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        $movie = Movie::create(['title' => 'Avatar 2', 'duration' => 192]);
        $room = Room::create(['name' => 'Phòng 5', 'total_seats' => 1]);
        $seat = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 5, 'type' => 'STANDARD']);

        // Suất chiếu chỉ cách 1 tiếng nữa (Không đủ điều kiện >= 2 tiếng)
        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(4),
            'price_standard' => 100000,
        ]);

        $booking = Booking::create([
            'user_id' => $customer->id,
            'total_price' => 100000,
            'status' => 'PAID',
        ]);

        TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'final_price' => 100000,
            'status' => 'BOOKED',
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'method' => 'BANK_TRANSFER',
            'status' => 'PAID',
            'amount' => 100000,
            'payment_code' => 'PAY-AVA123',
            'paid_at' => now(),
        ]);

        $this->actingAs($customer);
        $responseRefund = $this->post(route('user.account.bookings.refund', $booking->id));

        // Bị từ chối vì quá sát giờ chiếu
        $responseRefund->assertRedirect(route('user.account.tickets'));
        $responseRefund->assertSessionHas('error');

        // Không có bản ghi refund nào được tạo
        $this->assertDatabaseMissing('refunds', [
            'amount' => 100000,
        ]);
    }

    public function test_admin_revenue_dashboard(): void
    {
        $admin = User::create([
            'username' => 'revenue_admin',
            'email' => 'rev_admin@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
        ]);

        $this->actingAs($admin);
        $response = $this->get(route('admin.revenue.index'));
        $response->assertStatus(200);
        $response->assertSee('Doanh thu thuần');
    }

    public function test_staff_can_checkin_and_update_ticket_status(): void
    {
        $staff = User::create([
            'username' => 'staff_cin',
            'email' => 'staff_cin@example.com',
            'password' => 'password123',
            'role' => 'STAFF',
        ]);

        $movie = Movie::create(['title' => 'Tenet', 'duration' => 150]);
        $room = Room::create(['name' => 'Phòng 6', 'total_seats' => 1]);
        $seat = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 1, 'type' => 'STANDARD']);
        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(3),
            'price_standard' => 100000,
        ]);

        $booking = Booking::create([
            'user_id' => $staff->id,
            'total_price' => 100000,
            'status' => 'PAID',
        ]);

        $ticket = TicketDetail::create([
            'booking_id' => $booking->id,
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'final_price' => 100000,
            'status' => 'BOOKED',
        ]);

        $this->actingAs($staff);
        $response = $this->post(route('staff.tickets.updateStatus', $ticket->id), [
            'status' => 'USED',
        ]);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ticket_details', [
            'id' => $ticket->id,
            'status' => 'USED',
        ]);
    }

    public function test_customer_can_view_all_tickets_in_booking_details(): void
    {
        $customer = User::create([
            'username' => 'multiseat_cust',
            'email' => 'multi@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        $movie = Movie::create(['title' => 'Inception', 'duration' => 148]);
        $room = Room::create(['name' => 'Phòng 7', 'total_seats' => 3]);
        $seat1 = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 1, 'type' => 'STANDARD']);
        $seat2 = Seat::create(['room_id' => $room->id, 'seat_row' => 'A', 'seat_number' => 2, 'type' => 'STANDARD']);
        $seat3 = Seat::create(['room_id' => $room->id, 'seat_row' => 'E', 'seat_number' => 1, 'type' => 'VIP']);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'start_time' => now()->addHours(5),
            'end_time' => now()->addHours(8),
            'price_standard' => 100000,
        ]);

        $this->actingAs($customer);

        // Khách hàng đặt 3 vé trong cùng 1 đơn (A1, A2, E1 VIP)
        $this->post(route('ticket.store', $movie->id), [
            'showtime_id' => $showtime->id,
            'seat_ids' => [$seat1->id, $seat2->id, $seat3->id],
        ]);

        $booking = Booking::where('user_id', $customer->id)->latest('id')->first();
        $this->assertEquals(3, $booking->ticketDetails()->count());

        // Thanh toán toàn bộ đơn
        $this->post(route('user.account.bookings.pay', $booking->id), [
            'payment_method' => 'BANK_TRANSFER',
        ]);

        // Truy cập trang xem chi tiết đơn đặt vé qua Booking ID
        $response = $this->get(route('user.account.tickets.show', $booking->id));
        $response->assertStatus(200);

        // Kiểm tra trang hiển thị ĐẦY ĐỦ cả 3 vé trong đơn
        $response->assertSee('A1');
        $response->assertSee('A2');
        $response->assertSee('E1');
        $response->assertSee('Danh sách tất cả các vé trong đơn (3 vé)');

        // Truy cập qua ID của một vé lẻ thuộc đơn cũng phải hiển thị toàn bộ 3 vé của đơn đó
        $firstTicket = $booking->ticketDetails()->first();
        $responseByTicketId = $this->get(route('user.account.tickets.show', $firstTicket->id));
        $responseByTicketId->assertStatus(200);
        $responseByTicketId->assertSee('A1');
        $responseByTicketId->assertSee('A2');
        $responseByTicketId->assertSee('E1');
    }

    public function test_staff_and_admin_roles_and_access(): void
    {
        $admin = User::create([
            'username' => 'admin_test',
            'email' => 'admin_test@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
        ]);

        $staff = User::create([
            'username' => 'staff_test',
            'email' => 'staff_test@example.com',
            'password' => 'password123',
            'role' => 'STAFF',
        ]);

        $customer = User::create([
            'username' => 'cust_test',
            'email' => 'cust_test@example.com',
            'password' => 'password123',
            'role' => 'CUSTOMER',
        ]);

        // Customer không vào được trang Admin
        $this->actingAs($customer)->get('/admin')->assertStatus(403);
        $this->actingAs($customer)->get('/staff/bookings')->assertStatus(403);

        // Staff vào được trang Staff nhưng không vào được Admin
        $this->actingAs($staff)->get('/staff/bookings')->assertStatus(200);
        $this->actingAs($staff)->get('/admin')->assertStatus(403);

        // Admin vào được Admin và Staff
        $this->actingAs($admin)->get('/admin')->assertStatus(200);
        $this->actingAs($admin)->get('/staff/bookings')->assertStatus(200);
    }
}
