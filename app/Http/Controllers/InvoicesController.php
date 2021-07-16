<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Services\Invoices\InvoicesService;
use App\Transformers\InvoicesTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;

class InvoicesController extends ApiController
{

    protected $invoiceService;
    protected $response;

    function __construct(InvoicesService $invoiceService, Larasponse $response)
    {
        $this->invoiceService = $invoiceService;
        $this->response = $response;
        parent::__construct();
    }

    public function get_invoices()
    {
        $invoices = $this->invoiceService->getInvoices();
        return ApiResponse::success($this->response->collection($invoices, new InvoicesTransformer));
    }

    public function get_pdf($invoiceNumber)
    {
        try {
            $pdf = $this->invoiceService->getPdf($invoiceNumber);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        if (Request::get('download')) {
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'filename="invoice.pdf"'
            ];
        } else {
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'filename="invoice.pdf"'
            ];
        }
        return \response($pdf, 200, $headers);
    }
}
