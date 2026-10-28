<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
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
        $responseTicketsPage->assertSee('Đang giữ chỗ');

        // Thanh toán vé ghế thường
        $ticket = \App\Models\TicketDetail::where('seat_id', $seatStandard->id)->first();
        $responsePay = $this->post(route('user.account.tickets.payment', $ticket->id));
        $responsePay->assertRedirect(route('user.account.tickets.show', $ticket->id));

        $this->assertDatabaseHas('ticket_details', [
            'id' => $ticket->id,
            'status' => 'BOOKED',
        ]);
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
