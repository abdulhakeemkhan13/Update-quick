<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>General Journal</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .company-name { font-size: 16px; font-weight: bold; }
        .report-title { font-size: 14px; font-weight: bold; margin: 5px 0; }
        .date-range { font-size: 12px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f0f0f0; padding: 8px 4px; border: 1px solid #ccc; font-weight: bold; font-size: 9px; }
        td { padding: 4px; border: 1px solid #ccc; vertical-align: top; }
        
        .date-header { font-weight: bold; background-color: #f8f8f8; padding: 8px; border-top: 2px solid #000; }
        .journal-entry { background-color: #fff; }
        .journal-line { background-color: #fafafa; padding-left: 20px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->company_name }}</div>
        <div class="report-title">General Journal</div>
        <div class="date-range">{{ $startDate->format('F j, Y') }} to {{ $endDate->format('F j, Y') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">DATE</th>
                <th width="8%">TRANS #</th>
                <th width="12%">TYPE</th>
                <th width="8%">NUM</th>
                <th width="15%">NAME</th>
                <th width="15%">MEMO</th>
                <th width="20%">ACCOUNT</th>
                <th width="8%">DEBIT</th>
                <th width="8%">CREDIT</th>
            </tr>
        </thead>
        <tbody>
            @php $currentDate = null; @endphp
            @foreach($journalEntries as $entry)
                @php $entryDate = \Carbon\Carbon::parse($entry->date)->format('Y-m-d'); @endphp
                
                @if($currentDate !== $entryDate)
                    @php $currentDate = $entryDate; @endphp
                    <tr>
                        <td colspan="9" class="date-header">
                            {{ \Carbon\Carbon::parse($entry->date)->format('l, F j, Y') }}
                        </td>
                    </tr>
                @endif

                <tr class="journal-entry">
                    <td>{{ \Carbon\Carbon::parse($entry->date)->format('m/d/Y') }}</td>
                    <td>{{ $entry->journal_number ?? $entry->id }}</td>
                    <td>Journal Entry</td>
                    <td>{{ $entry->reference ?? '' }}</td>
                    <td>{{ $entry->description ?? '' }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>

                @foreach($entry->lines as $line)
                <tr class="journal-line">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ $line->memo ?? '' }}</td>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;{{ $line->chartOfAccount->name ?? 'Unknown Account' }}</td>
                    <td class="text-right">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                    <td class="text-right">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>