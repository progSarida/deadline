@php
    // Configurazione larghezza colonne
    $colWidths = [
        'ambito'         => '16%',   // Ambito
        'scadenza'       => '14%',   // Scadenza + badge
        'periodica'      => '7%',    // Periodica
        'rispettata'     => '7%',    // Rispettata
        'data_rispetto'  => '9%',    // Data rispetto
        'inserita_il'    => '9%',    // Inserita il
        'inserita_da'    => '10%',   // Inserita da
    ];
@endphp

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Elenco Scadenze</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            margin: 20px;
            line-height: 1.45;
        }
        h2 { text-align: center; text-decoration: underline; margin-bottom: 20px; }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid black;
            margin-top: 15px;
        }
        th, td {
            border-left: none;
            border-right: none;
            padding: 7px 9px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }
        thead th {
            border-top: 2px solid black;
            border-bottom: 2px solid black;
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr td:first-child, tr th:first-child { border-left: 2px solid black; }
        tr td:last-child,  tr th:last-child  { border-right:  2px solid black; }

        /* Riga descrizione */
        tr.description-row td {
            padding: 11px 14px;
            background-color: #fdfdfd;
            border-top: 1px solid #e0e0e0;
            font-size: 10.8px;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }

        /* Riga note */
        tr.note-row td {
            padding: 11px 14px;
            background-color: #f9f9f9;
            border-top: 1px dashed #ccc;
            font-size: 10.6px;
            color: #444;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }

        /* Separatore finale */
        tr.separator {
            border-bottom: 2px dashed black;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9px;
            margin-top: 4px;
        }
        .badge-danger  { background:#fca5a5; color:#991b1b; }
        .badge-warning { background:#fde047; color:#854d0e; }
        .badge-gray    { background:#e5e7eb; color:#374151; }
    </style>
</head>
<body>

<h2><u>Elenco Scadenze</u></h2>

{{-- Filtri applicati --}}
@if(!empty($filters) || $search)
    <p><strong>Filtri applicati:</strong></p>
    <ul style="margin:0 0 15px 20px; font-size:10px;">
        @if($search)<li>Ricerca: {{ $search }}</li>@endif
        @if(!empty($filters['scope_type_id']['values'] ?? []))
            <li>Ambito: {{ implode(', ', \App\Models\ScopeType::whereIn('id', $filters['scope_type_id']['values'])->pluck('name')->toArray()) }}</li>
        @endif
        @if(!empty($filters['timespan']['values'] ?? []))
            <li>Periodicità: {{ collect($filters['timespan']['values'])->map(fn($v) => $v === 'null' ? 'Non periodica' : (\App\Enums\Timespan::tryFrom($v)?->getLabel() ?? $v))->implode(', ') }}</li>
        @endif
        @if(!empty($filters['stato_scadenza']['value'] ?? null))
            <li>Stato scadenza: {{ ['respected'=>'Rispettate', 'not_met_late'=>'Non rispettate (scadute)', 'in_progress'=>'In corso'][$filters['stato_scadenza']['value']] }}</li>
        @endif
        @if(!empty($filters['deadline_period']['deadline_from']) || !empty($filters['deadline_period']['deadline_to']))
            <li>Periodo scadenza:
                @php
                    $from = $filters['deadline_period']['deadline_from'] ?? null;
                    $to   = $filters['deadline_period']['deadline_to'] ?? null;
                    echo $from && $to ? "dal $from al $to" : ($from ? "dal $from" : ($to ? "al $to" : ""));
                @endphp
            </li>
        @endif
    </ul>
@endif

<table>
    <thead>
        <tr>
            <th width="{{ $colWidths['ambito'] }}">Ambito</th>
            <th width="{{ $colWidths['scadenza'] }}">Scadenza</th>
            <th width="{{ $colWidths['periodica'] }}">Periodica</th>
            <th width="{{ $colWidths['rispettata'] }}">Rispettata</th>
            <th width="{{ $colWidths['data_rispetto'] }}">Data Rispetto</th>
            <th width="{{ $colWidths['inserita_il'] }}">Inserita il</th>
            <th width="{{ $colWidths['inserita_da'] }}">Inserita da</th>
        </tr>
    </thead>
    <tbody>
        @forelse($deadlines as $deadline)

            {{-- 1. Riga DATI PRINCIPALI --}}
            <tr>
                <td><strong>{{ $deadline->scopeType?->name ?? 'N/D' }}</strong></td>
                <td>
                    {{ $deadline->deadline_date ? \Carbon\Carbon::parse($deadline->deadline_date)->format('d/m/Y') : 'N/D' }}
                    @php
                        $days = $deadline->deadline_date
                            ? now()->startOfDay()->diffInDays(
                                \Carbon\Carbon::parse($deadline->deadline_date)->startOfDay(),
                                false
                            )
                            : null;
                    @endphp
                    @if($days !== null && !$deadline->met)
                        <span>
                            {{ $days < 0 ? 'SCADUTA' : ($days == 0 ? 'OGGI' : $days.' gg') }}
                        </span>
                    @endif
                </td>
                <td>
                    @if($deadline->recurrent)
                        {{ $deadline->quantity }} {{ $deadline->timespan?->getLabel() ?? '' }}
                    @else
                        No
                    @endif
                </td>
                <td>
                    @if(!$deadline->deadline_date || (!$deadline->met && \Carbon\Carbon::parse($deadline->deadline_date)->isFuture()))
                        -
                    @else
                        {{ $deadline->met ? 'Sì' : 'No' }}
                    @endif
                </td>
                <td>{{ $deadline->met_date ? \Carbon\Carbon::parse($deadline->met_date)->format('d/m/Y') : '–' }}</td>
                <td>{{ $deadline->created_at?->format('d/m/Y') ?? '–' }}</td>
                <td>{{ $deadline->insertUser?->name ?? '–' }}</td>
            </tr>

            {{-- 2. Riga DESCRIZIONE (sempre presente) --}}
            <tr class="description-row">
                <td colspan="7">
                    {!! $deadline->description
                        ? nl2br(e($deadline->description))
                        : '<em style="color:#999;">Nessuna descrizione inserita</em>'
                    !!}
                </td>
            </tr>

            {{-- 3. Riga NOTE (sempre presente) --}}
            <tr class="note-row">
                <td colspan="7">
                    {!! $deadline->note
                        ? nl2br(e($deadline->note))
                        : '<em style="color:#999;">Nessuna nota</em>'
                    !!}
                </td>
            </tr>

            {{-- Separatore tra una scadenza e l'altra --}}
            <tr class="separator"><td colspan="7"></td></tr>

        @empty
            <tr>
                <td colspan="7" style="text-align:center; padding:40px; color:#888; font-style:italic;">
                    Nessuna scadenza trovata con i criteri selezionati.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:40px; font-size:10px; color:#666; text-align:center;">
    Stampato il {{ now()->format('d/m/Y \o\r\e H:i') }} da {{ auth()->user()->name ?? 'Utente' }}
</div>

</body>
</html>
