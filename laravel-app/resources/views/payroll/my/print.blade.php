<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payslip – {{ $payrollRecord->payrollPeriod->name }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #111; background: #fff; max-width: 800px; margin: 0 auto; padding: 32px 24px; }
  .no-print { margin-bottom: 24px; display: flex; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid #eee; }
  .btn-print { background: #3525cd; color: #fff; padding: 10px 24px; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; font-weight: 600; }
  .btn-print:hover { background: #2a1fb0; }
  .btn-back { color: #3525cd; text-decoration: none; font-size: 14px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 16px; margin-bottom: 24px; }
  .header h1 { font-size: 26px; font-weight: 700; letter-spacing: 2px; color: #3525cd; }
  .header .period-info { font-size: 13px; color: #555; margin-top: 4px; }
  .header .right { text-align: right; font-size: 13px; }
  .header .status-badge { display: inline-block; background: #e7eefe; color: #3525cd; padding: 3px 10px; border-radius: 20px; font-weight: 700; font-size: 12px; letter-spacing: 0.05em; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  table th, table td { padding: 9px 12px; border: 1px solid #ddd; text-align: left; font-size: 13px; }
  table thead th { background: #f5f6ff; font-weight: 700; color: #3525cd; }
  table tfoot th { background: #f5f6ff; font-weight: 700; }
  .text-right { text-align: right; }
  .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #555; margin-bottom: 8px; }
  .net-salary-box { border: 2px solid #3525cd; border-radius: 8px; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
  .net-salary-box .label { font-size: 16px; font-weight: 700; color: #555; }
  .net-salary-box .amount { font-size: 24px; font-weight: 700; color: #3525cd; }
  .footer { font-size: 11px; color: #888; border-top: 1px solid #eee; padding-top: 12px; margin-top: 8px; }
  @media print {
    .no-print { display: none; }
    body { padding: 16px; }
    .net-salary-box { border-color: #000; }
    .net-salary-box .amount { color: #000; }
    .header h1 { color: #000; }
    table thead th { color: #000; }
  }
  @media (max-width: 640px) {
    body { padding: 20px 12px; }
    .no-print { flex-wrap: wrap; gap: 10px; }
    .btn-print { width: 100%; }
    .header { flex-direction: column; gap: 12px; }
    .header .right { text-align: left; }
    table { display: block; overflow-x: auto; white-space: nowrap; }
    .net-salary-box { flex-direction: column; align-items: flex-start; gap: 8px; }
    .net-salary-box .amount { font-size: 20px; }
  }
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">&#x1F5A8; Print Payslip</button>
  <a class="btn-back" href="{{ route('my.payroll.show', $payrollRecord) }}">← Back to Payslip</a>
</div>

@php
  $period         = $payrollRecord->payrollPeriod;
  $employee       = $payrollRecord->employee;
  $gross          = (float) $payrollRecord->basic_salary + (float) $payrollRecord->allowance
                  + (float) $payrollRecord->bonus + (float) $payrollRecord->overtime;
  $totalDeductions = (float) $payrollRecord->deduction + (float) $payrollRecord->late_deduction
                   + (float) $payrollRecord->attendance_deduction + (float) $payrollRecord->tax_bpjs;
@endphp

<div class="header">
  <div>
    <h1>PAYSLIP</h1>
    <p class="period-info">{{ $period->name }}</p>
    <p class="period-info">{{ $period->start_date->format('d M Y') }} – {{ $period->end_date->format('d M Y') }}</p>
  </div>
  <div class="right">
    <span class="status-badge">{{ str_replace('_', ' ', $period->status) }}</span>
    @if($period->pay_date)
      <p style="margin-top: 8px;">Pay Date: {{ $period->pay_date->format('d M Y') }}</p>
    @endif
  </div>
</div>

{{-- Employee Info --}}
<p class="section-title">Employee Information</p>
<table>
  <tbody>
    <tr>
      <th style="width:40%">Employee Name</th>
      <td>{{ $employee->user->name ?? '—' }}</td>
    </tr>
    <tr>
      <th>Employee Number (NIK)</th>
      <td>{{ $employee->nik }}</td>
    </tr>
    @if($employee->position)
    <tr>
      <th>Position</th>
      <td>{{ $employee->position->name }}</td>
    </tr>
    @endif
    @if($employee->department)
    <tr>
      <th>Department</th>
      <td>{{ $employee->department->name }}</td>
    </tr>
    @endif
    <tr>
      <th>Attendance Days</th>
      <td>{{ $payrollRecord->attendance_days }} days</td>
    </tr>
    <tr>
      <th>Leave Days</th>
      <td>{{ number_format((float) $payrollRecord->leave_days, 1) }} days</td>
    </tr>
  </tbody>
</table>

{{-- Earnings --}}
<p class="section-title">Earnings</p>
<table>
  <thead>
    <tr><th>Description</th><th class="text-right">Amount (IDR)</th></tr>
  </thead>
  <tbody>
    <tr>
      <td>Basic Salary</td>
      <td class="text-right">Rp {{ number_format((float) $payrollRecord->basic_salary, 0, ',', '.') }}</td>
    </tr>
    @if((float) $payrollRecord->allowance > 0)
    <tr>
      <td>Allowance</td>
      <td class="text-right">Rp {{ number_format((float) $payrollRecord->allowance, 0, ',', '.') }}</td>
    </tr>
    @endif
    @if((float) $payrollRecord->bonus > 0)
    <tr>
      <td>Bonus</td>
      <td class="text-right">Rp {{ number_format((float) $payrollRecord->bonus, 0, ',', '.') }}</td>
    </tr>
    @endif
    @if((float) $payrollRecord->overtime > 0)
    <tr>
      <td>Overtime</td>
      <td class="text-right">Rp {{ number_format((float) $payrollRecord->overtime, 0, ',', '.') }}</td>
    </tr>
    @endif
  </tbody>
  <tfoot>
    <tr>
      <th>Total Earnings</th>
      <th class="text-right">Rp {{ number_format($gross, 0, ',', '.') }}</th>
    </tr>
  </tfoot>
</table>

{{-- Deductions --}}
<p class="section-title">Deductions</p>
<table>
  <thead>
    <tr><th>Description</th><th class="text-right">Amount (IDR)</th></tr>
  </thead>
  <tbody>
    @if((float) $payrollRecord->deduction > 0)
    <tr>
      <td>General Deduction</td>
      <td class="text-right">Rp {{ number_format((float) $payrollRecord->deduction, 0, ',', '.') }}</td>
    </tr>
    @endif
    @if((float) $payrollRecord->late_deduction > 0)
    <tr>
      <td>Late Deduction</td>
      <td class="text-right">Rp {{ number_format((float) $payrollRecord->late_deduction, 0, ',', '.') }}</td>
    </tr>
    @endif
    @if((float) $payrollRecord->attendance_deduction > 0)
    <tr>
      <td>Attendance Deduction</td>
      <td class="text-right">Rp {{ number_format((float) $payrollRecord->attendance_deduction, 0, ',', '.') }}</td>
    </tr>
    @endif
    @if((float) $payrollRecord->tax_bpjs > 0)
    <tr>
      <td>Tax &amp; BPJS</td>
      <td class="text-right">Rp {{ number_format((float) $payrollRecord->tax_bpjs, 0, ',', '.') }}</td>
    </tr>
    @endif
    @if($totalDeductions === 0.0)
    <tr>
      <td colspan="2" style="text-align:center; color:#888;">No deductions</td>
    </tr>
    @endif
  </tbody>
  <tfoot>
    <tr>
      <th>Total Deductions</th>
      <th class="text-right">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</th>
    </tr>
  </tfoot>
</table>

{{-- Net Salary --}}
<div class="net-salary-box">
  <span class="label">NET SALARY</span>
  <span class="amount">Rp {{ number_format((float) $payrollRecord->net_salary, 0, ',', '.') }}</span>
</div>

<p class="footer">Generated: {{ now()->format('d M Y, H:i:s') }} &nbsp;|&nbsp; This is a computer-generated payslip.</p>

</body>
</html>
