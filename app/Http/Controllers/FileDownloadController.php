<?php

namespace App\Http\Controllers;

use App\Models\OrderFile;
use App\Services\OrderFileService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only way to reach an order file.
 *
 * Sits outside every role-prefixed route group: `user-access` is single-role,
 * so a download route placed inside any one group would be unreachable by the
 * other three. One route, guarded by OrderFilePolicy, shared by all four roles.
 */
class FileDownloadController extends Controller
{
    public function __construct(private readonly OrderFileService $files) {}

    public function __invoke(OrderFile $orderFile): StreamedResponse
    {
        // Resolves the file back to its order and defers to OrderPolicy@view,
        // so "who may see this order" is answered in exactly one place.
        $this->authorize('download', $orderFile);

        return $this->files->download($orderFile);
    }
}
