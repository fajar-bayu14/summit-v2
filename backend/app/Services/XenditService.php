<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\Invoice;
use Xendit\Invoice\InvoiceApi;

class XenditService
{
    /**
     * Invoice validity window in seconds (24 hours).
     */
    protected const INVOICE_DURATION = 86400;

    /**
     * Inject the Xendit Invoice API client.
     */
    public function __construct(
        protected InvoiceApi $invoiceApi
    ) {
        $secretKey = (string) config('services.xendit.secret_key');

        if ($secretKey !== '') {
            $this->invoiceApi->setApiKey($secretKey);
        }
    }

    /**
     * Create a Xendit invoice for the given pending order.
     *
     * @throws XenditSdkException|GuzzleException
     */
    public function createInvoice(Pesanan $pesanan, Pembayaran $pembayaran): Invoice
    {
        $request = new CreateInvoiceRequest([
            'external_id' => $pesanan->invoice,
            'amount' => (float) $pesanan->total_bayar,
            'payer_email' => $pesanan->user->email,
            'description' => 'Pembayaran pesanan '.$pesanan->invoice.' - '.$pesanan->basecamp->nama_basecamp,
            'invoice_duration' => self::INVOICE_DURATION,
            'should_send_email' => false,
            'currency' => 'IDR',
            'metadata' => [
                'pesanan_id' => $pesanan->id,
                'pembayaran_id' => $pembayaran->id,
            ],
        ]);

        return $this->invoiceApi->createInvoice(
            $request,
            null,
            'application/json'
        );
    }

    /**
     * Retrieve an invoice from Xendit API by its ID.
     */
    public function getInvoice(string $xenditInvoiceId): ?Invoice
    {
        if ($xenditInvoiceId === '') {
            return null;
        }

        try {
            return $this->invoiceApi->getInvoiceById($xenditInvoiceId);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengambil data invoice dari Xendit', [
                'xendit_invoice_id' => $xenditInvoiceId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
