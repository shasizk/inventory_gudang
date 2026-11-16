<?php

namespace App\Http\Controllers;

use App\Models\RfidCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RfidCardController extends Controller
{
    /**
     * Menampilkan daftar kartu RFID di halaman User Management -> Kartu.
     */
    public function index(Request $request): View
    {
        $query = RfidCard::query();

        // Filter pencarian UID atau Nama
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('uid', 'like', "%{$search}%");
            });
        }

        // Filter Peran / Role
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        $cards = $query->orderBy('name', 'asc')->get();

        return view('pages.users.cards', compact('cards'));
    }

    /**
     * Mendaftarkan kartu baru dari modal web.
     */
    public function store(Request $request): RedirectResponse
    {
        // Normalisasi UID: hapus semua spasi, jadikan kapital
        $cleanUid = strtoupper(preg_replace('/\s+/', '', $request->input('uid')));
        $request->merge(['uid' => $cleanUid]);
        $validated = $request->validate([
            'uid' => ['required', 'string', 'max:50', 'unique:rfid_cards,uid'],
            'name' => ['required', 'string', 'max:80'],
            'role' => ['required', 'string', 'in:owner,admin,teknisi,noc'],
            'active' => ['nullable'],
        ]);

        // Format UID agar rapi: bersihkan spasi ganda, jadikan huruf kapital
        $validated['uid'] = strtoupper(preg_replace('/\s+/', '', trim($validated['uid'])));
        $validated['active'] = $request->boolean('active', true);

        RfidCard::create($validated);

        return redirect()->route('cards.index')->with('success', "Kartu untuk {$validated['name']} berhasil didaftarkan.");
    }

    /**
     * Memperbarui data kartu (Nama, Role, atau status Aktif/Blokir).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $card = RfidCard::findOrFail($id);

        $validated = $request->validate([
            'uid' => ['required', 'string', 'max:50', 'unique:rfid_cards,uid,' . $card->id],
            'name' => ['required', 'string', 'max:80'],
            'role' => ['required', 'string', 'in:owner,admin,teknisi,noc'],
            'active' => ['nullable'],
        ]);

        $validated['uid'] = strtoupper(preg_replace('/\s+/', '', trim($validated['uid'])));
        $validated['active'] = $request->boolean('active', false);

        $card->update($validated);

        return redirect()->route('cards.index')->with('success', "Data kartu {$card->name} berhasil diperbarui.");
    }

    /**
     * Menghapus kartu dari sistem.
     */
    public function destroy(int $id): RedirectResponse
    {
        $card = RfidCard::findOrFail($id);
        $name = $card->name;
        $card->delete();

        return redirect()->route('cards.index')->with('success', "Kartu {$name} telah dihapus dari sistem.");
    }
}

