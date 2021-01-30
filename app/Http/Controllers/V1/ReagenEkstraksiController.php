<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReagenEkstraksiRequest;
use App\Models\ReagenEkstraksi;
use Illuminate\Http\Request;

class ReagenEkstraksiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search             = $request->get('search', false);
        $order              = $request->get('order', 'nama');
        $page               = $request->get('page', 1);
        $perpage            = $request->get('perpage', 20);
        $order_direction    = $request->get('order_direction', 'asc');

        $models = ReagenEkstraksi::query();
        if ($search != '') {
            $models = $models->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', '%' . $search . '%')
                    ->orWhere('metode_ekstraksi', $search);
            });
        }
        $count = $models->count();

        switch ($order) {
            case 'nama':
            case 'metode_ekstraksi':
                $models = $models->orderBy($order, $order_direction);
                break;
        }

        $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();

        $result = [
            'data' => $models,
            'count' => $count,
        ];

        return response()->json($result);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ReagenEkstraksiRequest $request)
    {
        $reagenEkstraksi = new ReagenEkstraksi;
        $reagenEkstraksi->fill($request->validated());
        $reagenEkstraksi->save();
        return response()->json(['result' => $reagenEkstraksi]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ReagenEkstraksi $reagenEkstraksi)
    {
        return response()->json(['result' => $reagenEkstraksi]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ReagenEkstraksiRequest $request, ReagenEkstraksi $reagenEkstraksi)
    {
        $reagenEkstraksi->fill($request->validated());
        $reagenEkstraksi->save();
        return response()->json(['result' => $reagenEkstraksi]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReagenEkstraksi $reagenEkstraksi)
    {
        $reagenEkstraksi->delete();
        return response()->json(['message' => 'DELETED']);
    }
}
