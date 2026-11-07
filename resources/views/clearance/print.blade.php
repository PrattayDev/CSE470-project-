<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Clearance Certificate - {{ $certificate->certificate_serial }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { padding: 0 !important; background: #fff !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="p-8 md:p-12 bg-slate-100 text-slate-900">
    <div class="max-w-4xl mx-auto bg-white border-8 border-double border-indigo-950 p-10 shadow-xl relative">
        <div class="flex items-start justify-between border-b-2 border-slate-800 pb-6 mb-6">
            <div>
                <div class="text-xs font-bold uppercase tracking-widest text-indigo-700">BRAC University • School of Data and Sciences</div>
                <h1 class="text-2xl md:text-3xl font-extrabold uppercase tracking-wider mt-1">Department of Computer Science & Engineering</h1>
                <h2 class="text-lg font-semibold text-indigo-800 mt-1">CSE 470: Software Engineering — Official Project & Lab Clearance Certificate</h2>
                <p class="text-xs text-slate-600 mt-2">Certificate Serial: <strong class="font-mono text-slate-900">{{ $certificate->certificate_serial }}</strong></p>
            </div>
            <div class="text-center border p-2 rounded bg-slate-50">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode($certificate->certificate_serial . ' | ' . $certificate->team->group_name) }}" alt="Verification QR" class="w-20 h-20 mx-auto">
                <div class="text-[10px] font-mono text-slate-500 mt-1">SCAN TO VERIFY</div>
            </div>
        </div>

        <p class="text-sm leading-relaxed mb-6">
            This is to certify that project group <strong>{{ $certificate->team->group_name }}</strong> (Section <strong>{{ $certificate->team->section }}</strong>) has successfully completed all 4 weekly sprints, submitted their final MVC source code and approved SRS documentation ({{ $certificate->srs_approved ? 'Verified' : 'Pending' }}), and returned all borrowed hardware assets ({{ $certificate->unreturned_equipment_count }} unreturned items; {{ $certificate->outstanding_fine_bdt }} BDT outstanding fine).
        </p>

        <h3 class="font-bold text-xs uppercase tracking-wider text-slate-600 mb-2">Cleared Team Members & Assigned Modules:</h3>
        <table class="w-full text-left border-collapse border border-slate-300 text-xs mb-8">
            <thead class="bg-slate-100">
                <tr>
                    <th class="border border-slate-300 p-2">Student Name</th>
                    <th class="border border-slate-300 p-2">Student ID</th>
                    <th class="border border-slate-300 p-2">Assigned CSE 470 Module</th>
                </tr>
            </thead>
            <tbody>
                @foreach($certificate->team->members as $m)
                    <tr>
                        <td class="border border-slate-300 p-2 font-semibold">{{ $m->student->name ?? '' }}</td>
                        <td class="border border-slate-300 p-2 font-mono">{{ $m->student->student_or_faculty_id ?? '' }}</td>
                        <td class="border border-slate-300 p-2">{{ $m->assigned_module }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="grid grid-cols-3 gap-6 pt-10 border-t border-slate-300 text-xs text-center">
            <div>
                <div class="border-t border-slate-700 pt-2 font-bold">Project Supervisor</div>
                <div class="text-slate-500">{{ $certificate->team->supervisor->name ?? 'Faculty Supervisor' }}</div>
            </div>
            <div>
                <div class="border-t border-slate-700 pt-2 font-bold">Hardware Lab Administrator</div>
                <div class="text-slate-500">Zero-Liability Verified</div>
            </div>
            <div>
                <div class="border-t border-slate-700 pt-2 font-bold">CSE 470 Course Coordinator</div>
                <div class="text-slate-500">Issued: {{ $certificate->issued_at }}</div>
            </div>
        </div>

        <div class="mt-8 text-center no-print">
            <button onclick="window.print()" class="bg-indigo-700 hover:bg-indigo-800 text-white px-5 py-2 rounded-lg text-xs font-bold shadow">
                Print / Save Official PDF Certificate
            </button>
        </div>
    </div>
</body>
</html>
