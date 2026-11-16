<?php

namespace App\Http\Controllers;

use App\Models\DoorLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DoorLogController extends Controller
{
    /**
     * Menampilkan riwayat akses pintu di menu History -> Riwayat Pintu.
     */
    public function index(Request $request): View
    {
        $logs = $this->filteredLogs($request)->orderByDesc('at')->paginate(25)->withQueryString();

        return view('pages.history.door', [
            'logs' => $logs,
            'latestLogId' => (int) (DoorLog::max('id') ?? 0),
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
            'updated_after' => ['nullable', 'date'],
        ]);

        $after = (int) ($validated['after'] ?? 0);
        $query = $this->filteredLogs($request);

        $query->where(function (Builder $query) use ($after, $validated) {
            $query->where('id', '>', $after);

            if (! empty($validated['updated_after'])) {
                $query->orWhere('updated_at', '>=', $validated['updated_after']);
            }
        });

        $logs = $query->orderByDesc('at')->limit(25)->get();
        $html = $logs->map(fn (DoorLog $log) => view('pages.history.partials.door-log-row', compact('log'))->render())
            ->implode('');
        $latestId = max($after, (int) ($logs->max('id') ?? $after));

        return response()->json([
            'html' => $html,
            'latest_id' => $latestId,
            'updated_after' => now()->format('Y-m-d H:i:s'),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function filteredLogs(Request $request): Builder
    {
        $query = DoorLog::query();

        if ($search = $request->input('q', $request->input('search'))) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('uid', 'like', "%{$search}%");
            });
        }

        if ($door = $request->input('door')) {
            $query->where('door', $door);
        }

        if ($direction = $request->input('direction')) {
            $query->where('direction', $direction);
        }

        if ($result = $request->input('result')) {
            $query->where('result', $result);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('at', $date);
        }

        return $query;
    }
}
