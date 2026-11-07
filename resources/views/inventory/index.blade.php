@extends('layouts.app')
@section('content')
<h2 class="text-2xl font-bold mb-1">Features 12 & 13: Lab Hardware Inventory & Requisition Penalty Workflow</h2>
<p class="text-sm text-slate-500 mb-6">Track university lab hardware stock, student requisitions, Lab Admin approval/issuance, and automated overdue return fine calculation.</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    @if(in_array(auth()->user()->role ?? '', ['lab_admin', 'coordinator'], true))
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Feature 12: Add Hardware Asset to Lab (Lab Admin)</h3>
        <form method="POST" action="{{ route('inventory.equipment.store') }}" class="space-y-3 text-sm">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <input type="text" name="asset_code" placeholder="BRACU-SEN-09" required class="border rounded px-3 py-1.5">
                <input type="text" name="equipment_name" placeholder="ESP32 Wi-Fi Camera Kit" required class="border rounded px-3 py-1.5">
            </div>
            <div class="grid grid-cols-4 gap-2">
                <input type="text" name="category" value="IoT / Embedded" required class="border rounded px-2 py-1.5">
                <input type="number" name="total_quantity" value="5" min="1" required class="border rounded px-2 py-1.5">
                <input type="number" name="daily_late_fine_bdt" value="50" min="0" required class="border rounded px-2 py-1.5">
                <select name="condition_status" class="border rounded px-2 py-1.5">
                    <option value="excellent">Excellent</option>
                    <option value="good">Good</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="damaged">Damaged</option>
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded font-semibold">Add Equipment</button>
        </form>
    </div>
    @endif

    @if((auth()->user()->role ?? '') === 'student')
    <div class="bg-white p-5 rounded-xl shadow-sm border">
        <h3 class="font-bold mb-3">Feature 13: Request Lab Equipment (Student)</h3>
        <form method="POST" action="{{ route('inventory.requisition.store') }}" class="space-y-3 text-sm">
            @csrf
            <div>
                <label class="block text-xs font-medium mb-1">Select Team</label>
                <select name="team_id" class="w-full border rounded px-3 py-1.5">
                    @foreach($teams as $t)
                        <option value="{{ $t->id }}">{{ $t->group_name }}</option>
                    @endforeach
                </select>
            </div>
            <select name="equipment_id" class="w-full border rounded px-3 py-1.5">
                @foreach($equipments as $eq)
                    <option value="{{ $eq->id }}">{{ $eq->asset_code }} - {{ $eq->equipment_name }} (Avail: {{ $eq->available_quantity }})</option>
                @endforeach
            </select>
            <div class="grid grid-cols-3 gap-2">
                <input type="number" name="quantity" value="1" min="1" class="border rounded px-2 py-1.5">
                <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" class="border rounded px-2 py-1.5">
                <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="border rounded px-2 py-1.5">
            </div>
            <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded font-semibold">Submit Requisition Request</button>
        </form>
    </div>
    @endif
</div>

<div class="bg-white p-6 rounded-xl shadow-sm border mb-6">
    <h3 class="font-bold mb-3">Feature 12: CSE Laboratory Hardware Catalog</h3>
    <table class="w-full text-left text-sm border">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-2.5">Asset Code</th>
                <th class="p-2.5">Equipment Name</th>
                <th class="p-2.5">Category</th>
                <th class="p-2.5">Available / Total Stock</th>
                <th class="p-2.5">Condition</th>
                <th class="p-2.5">Daily Overdue Fine</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($equipments as $eq)
            <tr>
                <td class="p-2.5 font-mono font-bold text-indigo-700">{{ $eq->asset_code }}</td>
                <td class="p-2.5 font-semibold">
                    {{ $eq->equipment_name }}
                    @if($eq->condition_notes)
                        <div class="text-[11px] text-slate-500 font-normal">{{ $eq->condition_notes }}</div>
                    @endif
                </td>
                <td class="p-2.5 text-slate-600">{{ $eq->category }}</td>
                <td class="p-2.5 font-bold">
                    {{ $eq->available_quantity }} / {{ $eq->total_quantity }}
                    @if($eq->isLowStock())
                        <span class="ml-1 px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[10px] uppercase font-bold">Low Stock</span>
                    @endif
                </td>
                <td class="p-2.5 uppercase text-xs font-bold">{{ $eq->condition_status }}</td>
                <td class="p-2.5 text-rose-600 font-semibold">{{ $eq->daily_late_fine_bdt }} BDT/day</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="bg-white p-6 rounded-xl shadow-sm border">
    <h3 class="font-bold mb-3">Active Requisitions & Late Return Penalty Calculator</h3>
    <table class="w-full text-left text-sm border">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-2.5">Asset</th>
                <th class="p-2.5">Team</th>
                <th class="p-2.5">Issue → Due Date</th>
                <th class="p-2.5">Status</th>
                <th class="p-2.5">Fine (BDT)</th>
                <th class="p-2.5">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($requisitions as $req)
            <tr>
                <td class="p-2.5 font-semibold">{{ $req->equipment->equipment_name ?? '' }} (x{{ $req->quantity }})</td>
                <td class="p-2.5">{{ $req->team->group_name ?? '' }}</td>
                <td class="p-2.5">{{ $req->issue_date }} → {{ $req->due_date }}</td>
                <td class="p-2.5 uppercase font-bold text-xs">{{ $req->status }}</td>
                <td class="p-2.5 font-bold text-rose-600">
                    {{ $req->calculated_fine_bdt }} BDT
                    @if(($req->fine_paid_amount ?? 0) > 0)
                        <div class="text-[10px] text-emerald-700 font-semibold">Paid: {{ $req->fine_paid_amount }} BDT</div>
                    @endif
                </td>
                <td class="p-2.5">
                    @if(in_array(auth()->user()->role ?? '', ['lab_admin', 'coordinator'], true))
                        @if(in_array($req->status, ['requested', 'approved'], true))
                            <form method="POST" action="{{ route('inventory.requisition.approve', $req) }}">
                                @csrf
                                <button type="submit" class="bg-indigo-600 text-white px-2.5 py-1 rounded text-xs font-semibold">Approve & Issue</button>
                            </form>
                        @elseif(in_array($req->status, ['issued', 'overdue'], true))
                            <form method="POST" action="{{ route('inventory.requisition.return', $req) }}" class="flex space-x-1">
                                @csrf
                                <input type="date" name="returned_date" value="{{ date('Y-m-d') }}" min="{{ $req->issue_date }}" class="border rounded px-2 py-0.5 text-xs">
                                <button type="submit" class="bg-slate-800 text-white px-2.5 py-1 rounded text-xs">Return & Calc Fine</button>
                            </form>
                        @else
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-emerald-600 font-semibold">Returned ({{ $req->returned_date }})</span>
                                @if($req->calculated_fine_bdt > 0)
                                    <form method="POST" action="{{ route('inventory.requisition.pay', $req) }}">
                                        @csrf
                                        <button type="submit" class="bg-emerald-600 text-white px-2 py-0.5 rounded text-xs font-semibold">Settle Fine</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @else
                        <span class="text-xs text-slate-600">{{ strtoupper($req->status) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
