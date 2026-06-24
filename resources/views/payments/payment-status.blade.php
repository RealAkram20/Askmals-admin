@php use App\Enums\Wallet\WalletTransactionStatusEnum; @endphp
@extends('layouts.no-head-foot')

@section('title', $paymentContext['title'] ?? __('labels.payment_summary'))
@section('content')
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <a href="." class="navbar-brand navbar-brand-autodark">
                @if(($systemSettings['demoMode'] ?? false))
                    <img
                        src="{{asset('logos/hyper-local-logo.png')}}"
                        alt="{{$systemSettings['appName'] ?? ""}}" width="150px">
                @else
                    <img
                        src="{{!empty($systemSettings['logo'])?$systemSettings['logo'] : asset('logos/hyper-local-logo.png')}}"
                        alt="{{$systemSettings['appName'] ?? ""}}" width="150px">
                @endif
            </a>
        </div>

        <div class="card card-md">
            <div class="card-body">
                <div class="text-center" style="font-size: 50px;">
                    @if(($transaction->status ?? '') === ($completedStatus ?? 'completed'))
                        <i class="ti ti-circle-check text-success"></i>
                    @elseif(($transaction->status ?? '') === ($failedStatus ?? 'failed'))
                        <i class="ti ti-circle-x text-danger"></i>
                    @else
                        <i class="ti ti-cancel text-warning"></i>
                    @endif
                    <h3 class="text-capitalize">{{ $transaction->status }}</h3>
                </div>
                <h2>{{ $paymentContext['title'] ?? __('labels.payment_summary') }}</h2>

                @foreach(($paymentContext['lineItems'] ?? []) as $item)
                    <div class="d-flex align-items-center justify-content-between mb-2 {{ $item['bold'] ?? false ? 'fw-bold' : '' }}">
                        <span>{{ $item['label'] }}:</span>
                        <span>{{ $item['value'] }}</span>
                    </div>
                @endforeach

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span>{{ __('labels.system_transaction_id') }}:</span>
                    <span>#{{ $transaction->id }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span>{{ __('labels.payment_id') }}:</span>
                    <span>{{ $transaction->transaction_id ?? $transaction->transaction_reference ?? '-' }}</span>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span>{{ __('labels.status') }}:</span>
                    <span class="fw-bold">{{ (string)($transaction->status ?? '-') }}</span>
                </div>

                <hr class="my-3"/>

                <div class="d-flex align-items-center justify-content-between h3">
                    <span>{{ __('labels.amount') }}</span>
                    <span>
                        {{ number_format((float)($transaction->amount ?? 0), 2) }}
                        {{ $systemSettings['currency'] ?? ($transaction->currency_code ?? $transaction->currency ?? '') }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-center">
                    <a href="{{ $paymentContext['backUrl'] ?? '#' }}" class="btn btn-primary">
                        {{ $paymentContext['backLabel'] ?? __('labels.go_back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
