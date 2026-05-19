<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Data dummy untuk simulasi IaaS
        $dummyData = [
            'storage_used'  => rand(10, 80),    // GB terpakai
            'storage_total' => 100,              // Total kuota GB
            'package'       => 'Candy Starter',  // Nama paket dummy
            'vcpu'          => 2,                // vCPU
            'ram'           => 4,                // RAM GB
            'instances'     => rand(1, 5),       // Instance aktif
            'uptime'        => '99.' . rand(50, 99) . '%',
        ];

        return view('dashboard', compact('user', 'dummyData'));
    }
}