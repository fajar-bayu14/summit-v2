<?php

use App\Http\Controllers\Admin\BannerAdController as AdminBannerAdController;
use App\Http\Controllers\Admin\BasecampController as AdminBasecampController;
use App\Http\Controllers\Admin\GunungController as AdminGunungController;
use App\Http\Controllers\Admin\JalurController as AdminJalurController;
use App\Http\Controllers\Admin\KycController as AdminKycController;
use App\Http\Controllers\Admin\MitraController as AdminMitraController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Ads\BannerAdController as PublicBannerAdController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Mitra\AnalyticsController as MitraAnalyticsController;
use App\Http\Controllers\Mitra\BasecampController as MitraBasecampController;
use App\Http\Controllers\Mitra\LogbookController as MitraLogbookController;
use App\Http\Controllers\Mitra\PesananController as MitraPesananController;
use App\Http\Controllers\Mitra\ProductController as MitraProductController;
use App\Http\Controllers\Mitra\ProfileController as MitraProfileController;
use App\Http\Controllers\Mitra\RefundController as MitraRefundController;
use App\Http\Controllers\Mitra\StaffController as MitraStaffController;
use App\Http\Controllers\Mitra\TrailController as MitraTrailController;
use App\Http\Controllers\Mitra\WalletController as MitraWalletController;
use App\Http\Controllers\Pendaki\BasecampController as PendakiBasecampController;
use App\Http\Controllers\Pendaki\CartController as PendakiCartController;
use App\Http\Controllers\Pendaki\GunungController as PendakiGunungController;
use App\Http\Controllers\Pendaki\KycController as PendakiKycController;
use App\Http\Controllers\Pendaki\LogbookController as PendakiLogbookController;
use App\Http\Controllers\Pendaki\PesananController as PendakiPesananController;
use App\Http\Controllers\Pendaki\ProductController as PendakiProductController;
use App\Http\Controllers\Pendaki\RefundController as PendakiRefundController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\XenditDisbursementWebhookController;
use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api')->name('login');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:api')->name('resend-otp');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

// Payment gateway webhooks. Guarded by constant-time x-callback-token verification.
Route::middleware(['xendit.webhook'])->group(function () {
    Route::post('/payments/webhook/xendit', [XenditWebhookController::class, 'handle'])->name('xendit.webhook');
    Route::post('/payments/webhook/xendit-disbursement', [XenditDisbursementWebhookController::class, 'handle'])->name('xendit.disbursement.webhook');
});

// Public Banner Ads endpoints
Route::get('/ads/banners', [PublicBannerAdController::class, 'index'])->name('ads.banners.index');
Route::post('/ads/banners/{id}/click', [PublicBannerAdController::class, 'click'])->name('ads.banners.click');

// Public Gunung, Jalur & Product catalog endpoints (Climber / Guests)
Route::get('/mountains', [PendakiGunungController::class, 'index'])->name('gunung.index');
Route::get('/mountains/{id}', [PendakiGunungController::class, 'show'])->name('gunung.show');
Route::get('/basecamps/{id}', [PendakiBasecampController::class, 'show'])->name('basecamps.show');
Route::get('/products', [PendakiProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [PendakiProductController::class, 'show'])->name('products.show');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // KYC endpoints for Climber (Pendaki)
    Route::post('/kyc/submit', [PendakiKycController::class, 'submit'])->name('kyc.submit');
    Route::get('/kyc/status', [PendakiKycController::class, 'status'])->name('kyc.status');

    // Booking transaction endpoints for Climber (Pendaki)
    Route::post('/orders', [PendakiPesananController::class, 'store'])->name('pesanan.store');
    Route::get('/orders', [PendakiPesananController::class, 'index'])->name('pesanan.index');
    Route::get('/orders/{invoice}', [PendakiPesananController::class, 'show'])->where('invoice', '[A-Za-z0-9_.-]+(/[A-Za-z0-9_.-]+){0,2}')->name('pesanan.show');
    Route::post('/orders/{invoice}/check-status', [PendakiPesananController::class, 'checkPaymentStatus'])->where('invoice', '[A-Za-z0-9_.-]+(/[A-Za-z0-9_.-]+){0,2}')->name('pesanan.check-status');
    Route::post('/orders/checkout', [PendakiPesananController::class, 'checkout'])->name('pesanan.checkout');
    Route::post('/orders/{invoice}/cancel', [PendakiPesananController::class, 'cancel'])->where('invoice', '[A-Za-z0-9_.-]+(/[A-Za-z0-9_.-]+){0,2}')->name('pesanan.cancel');
    Route::post('/orders/{invoice}/refund-request', [PendakiRefundController::class, 'store'])->where('invoice', '[A-Za-z0-9_.-]+(/[A-Za-z0-9_.-]+){0,2}')->name('refund.store');
    Route::post('/refunds/{id}/dispute', [PendakiRefundController::class, 'dispute'])->name('refund.dispute');

    // Digital Logbook & Summit Proof endpoints for Climber (Pendaki)
    Route::post('/orders/{invoice}/logbook', [PendakiLogbookController::class, 'store'])->where('invoice', '[A-Za-z0-9_.-]+(/[A-Za-z0-9_.-]+){0,2}')->name('logbook.store');
    Route::get('/orders/{invoice}/logbook', [PendakiLogbookController::class, 'show'])->where('invoice', '[A-Za-z0-9_.-]+(/[A-Za-z0-9_.-]+){0,2}')->name('logbook.show');
    Route::get('/orders/{invoice}/certificate', [PendakiLogbookController::class, 'certificate'])->where('invoice', '[A-Za-z0-9_.-]+(/[A-Za-z0-9_.-]+){0,2}')->name('logbook.certificate');
    Route::get('/pendaki/badges', [PendakiLogbookController::class, 'badges'])->name('pendaki.badges');

    // In-App Chat endpoints
    Route::post('/chat/rooms', [ChatController::class, 'createOrGetRoom'])->name('chat.rooms.store');
    Route::get('/chat/rooms', [ChatController::class, 'rooms'])->name('chat.rooms.index');
    Route::get('/chat/rooms/{room_id}/messages', [ChatController::class, 'messages'])->name('chat.messages.index');
    Route::post('/chat/rooms/{room_id}/messages', [ChatController::class, 'sendMessage'])->name('chat.messages.store');
    Route::post('/chat/rooms/{room_id}/read', [ChatController::class, 'markAsRead'])->name('chat.messages.read');

    // Cart endpoints for Climber (Pendaki)
    Route::get('/cart', [PendakiCartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [PendakiCartController::class, 'store'])->name('cart.store');
    Route::post('/cart/items', [PendakiCartController::class, 'addItem'])->name('cart.add-item');
    Route::patch('/cart/items/{itemId}', [PendakiCartController::class, 'updateItem'])->name('cart.update-item');
    Route::delete('/cart/items/{itemId}', [PendakiCartController::class, 'destroyItem'])->name('cart.destroy-item');
    Route::delete('/cart', [PendakiCartController::class, 'destroy'])->name('cart.destroy');

    // Admin endpoints (guarded by role:admin)
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        // KYC management
        Route::get('/kyc', [AdminKycController::class, 'index'])->name('admin.kyc.index');
        Route::get('/kyc/{id}', [AdminKycController::class, 'show'])->name('admin.kyc.show');
        Route::get('/kyc/{id}/download-document', [AdminKycController::class, 'downloadDocument'])->name('admin.kyc.download');
        Route::post('/kyc/{id}/verify', [AdminKycController::class, 'verify'])->name('admin.kyc.verify');

        // Gunung Management
        Route::post('/mountains', [AdminGunungController::class, 'store'])->name('admin.gunung.store');
        Route::match(['PUT', 'PATCH'], '/mountains/{id}', [AdminGunungController::class, 'update'])->name('admin.gunung.update');
        Route::post('/mountains/{id}', [AdminGunungController::class, 'update'])->name('admin.gunung.update-post');
        Route::delete('/mountains/{id}', [AdminGunungController::class, 'destroy'])->name('admin.gunung.destroy');

        // Jalur Pendakian Management
        Route::get('/trails', [AdminJalurController::class, 'index'])->name('admin.jalur.index');
        Route::post('/trails', [AdminJalurController::class, 'store'])->name('admin.jalur.store');
        Route::get('/trails/{id}', [AdminJalurController::class, 'show'])->name('admin.jalur.show');
        Route::match(['PUT', 'PATCH'], '/trails/{id}', [AdminJalurController::class, 'update'])->name('admin.jalur.update');
        Route::patch('/trails/{id}/status', [AdminJalurController::class, 'updateStatus'])->name('admin.jalur.update-status');
        Route::delete('/trails/{id}', [AdminJalurController::class, 'destroy'])->name('admin.jalur.destroy');

        // Mitra Management
        Route::get('/partners', [AdminMitraController::class, 'index'])->name('admin.mitra.index');
        Route::post('/partners', [AdminMitraController::class, 'store'])->name('admin.mitra.store');
        Route::get('/partners/{id}', [AdminMitraController::class, 'show'])->name('admin.mitra.show');
        Route::match(['PUT', 'PATCH'], '/partners/{id}', [AdminMitraController::class, 'update'])->name('admin.mitra.update');
        Route::delete('/partners/{id}', [AdminMitraController::class, 'destroy'])->name('admin.mitra.destroy');

        // Basecamp Management
        Route::get('/basecamps', [AdminBasecampController::class, 'index'])->name('admin.basecamp.index');
        Route::post('/basecamps', [AdminBasecampController::class, 'store'])->name('admin.basecamp.store');
        Route::get('/basecamps/{id}', [AdminBasecampController::class, 'show'])->name('admin.basecamp.show');
        Route::match(['PUT', 'PATCH'], '/basecamps/{id}', [AdminBasecampController::class, 'update'])->name('admin.basecamp.update');
        Route::delete('/basecamps/{id}', [AdminBasecampController::class, 'destroy'])->name('admin.basecamp.destroy');

        // Product Monitoring
        Route::get('/products', [AdminProductController::class, 'index'])->name('admin.products.index');
        Route::get('/products/{id}', [AdminProductController::class, 'show'])->name('admin.products.show');

        // Escrow, Withdrawal & Refund Management
        Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('admin.withdrawals.index');
        Route::post('/withdrawals/{id}/approve', [AdminWithdrawalController::class, 'approve'])->name('admin.withdrawals.approve');
        Route::post('/withdrawals/{id}/reject', [AdminWithdrawalController::class, 'reject'])->name('admin.withdrawals.reject');
        Route::get('/escrow/ledger', [AdminWithdrawalController::class, 'ledger'])->name('admin.escrow.ledger');
        Route::get('/refunds', [AdminRefundController::class, 'index'])->name('admin.refunds.index');
        Route::post('/refunds/{id}/process', [AdminRefundController::class, 'process'])->name('admin.refunds.process');
        Route::post('/refunds/force-majeure', [AdminRefundController::class, 'forceMajeure'])->name('admin.refunds.force-majeure');

        // Ads Banner Management (Admin)
        Route::get('/ads/banners', [AdminBannerAdController::class, 'index'])->name('admin.ads.banners.index');
        Route::post('/ads/banners', [AdminBannerAdController::class, 'store'])->name('admin.ads.banners.store');
        Route::get('/ads/banners/{id}', [AdminBannerAdController::class, 'show'])->name('admin.ads.banners.show');
        Route::match(['PUT', 'PATCH'], '/ads/banners/{id}', [AdminBannerAdController::class, 'update'])->name('admin.ads.banners.update');
        Route::post('/ads/banners/{id}', [AdminBannerAdController::class, 'update'])->name('admin.ads.banners.update-post');
        Route::delete('/ads/banners/{id}', [AdminBannerAdController::class, 'destroy'])->name('admin.ads.banners.destroy');
    });

    // Mitra endpoints (guarded by role:mitra)
    Route::middleware(['role:mitra'])->prefix('mitra')->group(function () {
        Route::put('/profile', [MitraProfileController::class, 'update'])->name('mitra.profile.update');

        Route::get('/products', [MitraProductController::class, 'index'])->name('mitra.products.index');
        Route::post('/products', [MitraProductController::class, 'store'])->name('mitra.products.store');
        Route::get('/products/{id}', [MitraProductController::class, 'show'])->name('mitra.products.show');
        Route::match(['PUT', 'PATCH'], '/products/{id}', [MitraProductController::class, 'update'])->name('mitra.products.update');
        Route::delete('/products/{id}', [MitraProductController::class, 'destroy'])->name('mitra.products.destroy');
        Route::patch('/products/{id}/toggle-status', [MitraProductController::class, 'toggleStatus'])->name('mitra.products.toggle-status');
        Route::get('/products/{id}/quotas', [MitraProductController::class, 'quotas'])->name('mitra.products.quotas');
        Route::post('/products/{id}/quotas/batch', [MitraProductController::class, 'batchQuotas'])->name('mitra.products.quotas.batch');
        Route::put('/quotas/{quota_id}', [MitraProductController::class, 'updateQuota'])->name('mitra.quotas.update');
        Route::patch('/products/{id}/stock', [MitraProductController::class, 'updateStock'])->name('mitra.products.stock');

        // Trail emergency closure for Mitra
        Route::post('/trails/{id}/emergency-close', [MitraTrailController::class, 'emergencyClose'])->name('mitra.trails.emergency-close');

        // Basecamp Management for Mitra
        Route::get('/basecamps', [MitraBasecampController::class, 'index'])->name('mitra.basecamp.index');
        Route::get('/basecamps/{id}', [MitraBasecampController::class, 'show'])->name('mitra.basecamp.show');
        Route::put('/basecamps/{id}', [MitraBasecampController::class, 'update'])->name('mitra.basecamp.update');

        // Staff Management for Mitra
        Route::get('/staff', [MitraStaffController::class, 'index'])->name('mitra.staff.index');
        Route::post('/staff', [MitraStaffController::class, 'store'])->name('mitra.staff.store');
        Route::get('/staff/{id}', [MitraStaffController::class, 'show'])->name('mitra.staff.show');
        Route::put('/staff/{id}', [MitraStaffController::class, 'update'])->name('mitra.staff.update');
        Route::delete('/staff/{id}', [MitraStaffController::class, 'destroy'])->name('mitra.staff.destroy');

        // Wallet, Escrow & Withdrawal for Mitra
        Route::get('/wallet', [MitraWalletController::class, 'summary'])->name('mitra.wallet.summary');
        Route::get('/wallet/ledger', [MitraWalletController::class, 'ledger'])->name('mitra.wallet.ledger');
        Route::post('/withdrawals', [MitraWalletController::class, 'withdraw'])->name('mitra.withdrawals.store');
        Route::get('/withdrawals', [MitraWalletController::class, 'withdrawals'])->name('mitra.withdrawals.index');

        // Digital Logbook Validation for Mitra
        Route::get('/logbooks', [MitraLogbookController::class, 'index'])->name('mitra.logbooks.index');
        Route::post('/logbooks/{id}/verify', [MitraLogbookController::class, 'verify'])->name('mitra.logbooks.verify');

        // Order Management for Mitra
        Route::get('/orders', [MitraPesananController::class, 'index'])->name('mitra.pesanan.index');
        Route::post('/orders/check-in', [MitraPesananController::class, 'checkInByCode'])->name('mitra.pesanan.check-in-code');
        Route::get('/orders/{id}', [MitraPesananController::class, 'show'])->name('mitra.pesanan.show');
        Route::get('/orders/{id}/ktp', [MitraPesananController::class, 'viewKycDocument'])->name('mitra.pesanan.ktp');
        Route::post('/orders/{id}/check-in', [MitraPesananController::class, 'checkIn'])->name('mitra.pesanan.check-in');
        Route::patch('/orders/{pesananId}/items/{itemId}', [MitraPesananController::class, 'updateItemStatus'])->name('mitra.pesanan.update-item');

        // Refund Management for Mitra (Tier-1 Review)
        Route::get('/refunds', [MitraRefundController::class, 'index'])->name('mitra.refunds.index');
        Route::get('/refunds/{id}', [MitraRefundController::class, 'show'])->name('mitra.refunds.show');
        Route::post('/refunds/{id}/approve', [MitraRefundController::class, 'approve'])->name('mitra.refunds.approve');
        Route::post('/refunds/{id}/reject', [MitraRefundController::class, 'reject'])->name('mitra.refunds.reject');

        // Analytics & Dashboard Summary for Mitra
        Route::get('/analytics/summary', [MitraAnalyticsController::class, 'summary'])->name('mitra.analytics.summary');
    });
});

Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Endpoint API tidak ditemukan (404).',
        'error_code' => 'ERR_NOT_FOUND',
    ], 404);
});
