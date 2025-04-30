@extends('core::base.layouts.master')
@section('title')
    {{ translate('Page Builder Updater') }}
@endsection

@section('custom_css')
@endsection

@section('main_content')
    <div class="row">
        <div class="col-md-7 mb-30 mx-auto">
            @if (count($errors) > 0)
                <div>
                    <ul class="p-0">
                        @foreach ($errors->all() as $error)
                            <p class="alert alert-danger">{{ $error }}</p>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h4>{{ translate('Page Builder Updater') }}</h4>
                </div>
                <div class="card-body">
                    @if ($update_available)
                        <p>{{ translate('Current version') }} {{ $current_version }}</p>
                        <p>{{ translate('Latest version') }} {{ $latest_version }}</p>
                        <a href="javascript::void(0)" class="btn long" data-toggle="modal"
                            data-target="#verify-purchase-modal">{{ translate('Update Now') }}
                        </a>
                    @else
                        <p>{{ translate('Page Builder is updated') }}</p>
                        <p>{{ translate('Current version') }} {{ $current_version }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Key Modal-->
    <div id="verify-purchase-modal" class="verify-purchase-modal modal fade show" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ translate('Verify Purchase Key') }}</h4>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('plugin.pagebuilder.update') }}">
                        @csrf
                        <label for="purchase_key" class="black bold font-14 mb-1">{{ translate('Purchase Key') }}</label>
                        <input type="text" id="purchase_key" name="purchase_key" class="form-control mb-2"
                            placeholder="{{ translate('Give Purchase Key To Update This Plugin') }}" required>
                        <button type="button" class="btn long mt-2 btn-danger"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn long mt-2">{{ translate('Update') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Purchase Key Modal-->
@endsection
