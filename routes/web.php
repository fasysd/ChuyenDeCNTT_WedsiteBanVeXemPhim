<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminMovieController;
use App\Http\Controllers\AdminRefundController;
use App\Http\Controllers\AdminRevenueController;
use App\Http\Controllers\AdminRoomController;
use App\Http\Controllers\AdminShowtimeController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TicketController;

/*
|--------------------------------------------------------------------------
| 1. KHÁCH (PUBLIC ROUTES)
|--------------------------------------------------------------------------
*/
Route::get('/', [MovieController::class, 'index'])->name('user.index');
Route::get('/movies', [MovieController::class, 'index'])->name('movies');
Route::get('/movies/{id}', [MovieController::class, 'show'])->name('movie.show');
Route::get('/help/about', function () {
    return view('user.help.about');
})->name('user.help.about');

/*
|--------------------------------------------------------------------------
| 2. XÁC THỰC (AUTHENTICATION)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| 3. NGƯỜI DÙNG / KHÁCH HÀNG (CUSTOMER - AUTH)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Thông tin tài khoản
    Route::get('/account/general', [AccountController::class, 'show'])->name('user.account.general');
    Route::get('/account/detail', [AccountController::class, 'edit'])->name('user.account.detail');
    Route::put('/account/detail', [AccountController::class, 'update'])->name('user.account.detail.update');
    Route::put('/account/password', [AccountController::class, 'resetPassword'])->name('user.account.password.update');

    // Đặt vé xem phim
    Route::get('/movies/{movie}/booking', [TicketController::class, 'booking'])->name('ticket.booking');
    Route::post('/movies/{movie}/purchase', [TicketController::class, 'purchase'])->name('ticket.purchase');
    Route::post('/movies/{movie}/ticket_store', [TicketController::class, 'store'])->name('ticket.store');
    Route::get('/movies/purchase_result', function () {
        return view('movie.purchase_result');
    })->name('ticket.result');

    // Quản lý vé cá nhân & Đơn đặt vé (Booking & Tickets)
    Route::get('/account/tickets', [TicketController::class, 'index'])->name('user.account.tickets');
    Route::get('/account/tickets/{id}', [TicketController::class, 'show'])->name('user.account.tickets.show');

    // Thao tác với Đơn đặt vé (Booking)
    Route::post('/account/bookings/{id}/pay', [TicketController::class, 'payBooking'])->name('user.account.bookings.pay');
    Route::post('/account/bookings/{id}/cancel', [TicketController::class, 'cancelBooking'])->name('user.account.bookings.cancel');
    Route::post('/account/bookings/{id}/refund', [TicketController::class, 'requestRefund'])->name('user.account.bookings.refund');

    // Thao tác với vé lẻ (Dự phòng tương thích)
    Route::post('/account/tickets/{id}/cancel', [TicketController::class, 'cancel'])->name('user.account.tickets.cancel');
    Route::post('/account/tickets/{id}/pay', [TicketController::class, 'payment'])->name('user.account.tickets.payment');
});

/*
|--------------------------------------------------------------------------
| 4. NHÂN VIÊN SOÁT VÉ (STAFF)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/bookings', [StaffController::class, 'bookings'])->name('bookings');
        Route::get('/tickets', [StaffController::class, 'tickets'])->name('tickets');
        Route::get('/tickets/{ticket}', [StaffController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/status', [StaffController::class, 'updateStatus'])->name('tickets.updateStatus');
    });

/*
|--------------------------------------------------------------------------
| 5. QUẢN TRỊ VIÊN (ADMIN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Trang chủ tổng quan Admin
        Route::get('/', [AdminMovieController::class, 'home'])->name('dashboard');

        // Quản lý Phim
        Route::resource('movies', AdminMovieController::class);

        // Quản lý Phòng chiếu
        Route::resource('rooms', AdminRoomController::class);

        // Quản lý Suất chiếu
        Route::resource('showtimes', AdminShowtimeController::class);

        // Quản lý Nhân viên
        Route::get('/staff', [AdminStaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [AdminStaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [AdminStaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}/edit', [AdminStaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [AdminStaffController::class, 'update'])->name('staff.update');
        Route::put('/staff/{staff}/reset-password', [AdminStaffController::class, 'resetPassword'])->name('staff.reset-password');

        // Quản lý Hoàn tiền (Refunds)
        Route::get('/refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
        Route::post('/refunds/{refund}/approve', [AdminRefundController::class, 'approve'])->name('refunds.approve');
        Route::post('/refunds/{refund}/reject', [AdminRefundController::class, 'reject'])->name('refunds.reject');

        // Quản lý Doanh thu (Revenue)
        Route::get('/revenue', [AdminRevenueController::class, 'index'])->name('revenue.index');
    });
