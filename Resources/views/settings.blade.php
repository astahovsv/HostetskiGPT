@extends('layouts.app')

@section('title_full', 'GPT Assistant - ' . $mailbox->name)

@section('body_attrs') 
    @parent data-mailbox_id="{{ $mailbox->id }}" 
@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="section-heading">
        GPT Assistant
    </div>
    <div class="col-xs-12">
        <form class="form-horizontal margin-top margin-bottom" method="POST" action="">
            {{ csrf_field() }}

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("OpenAI API key") }}</label>
                <div class="col-sm-6">
                    <input name="api_key" class="form-control" placeholder="sk-..." value="{{ $settings['api_key'] }}" required />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __("OpenAI Assistant ID") }}</label>
                <div class="col-sm-6">
                    <input name="assistant_id" class="form-control" placeholder="asst_..." value="{{ $settings['assistant_id'] }}" required />
                </div>
            </div>

            <div class="form-group margin-top margin-bottom">
                <div class="col-sm-6 col-sm-offset-2">
                    <button type="submit" class="btn btn-primary">
                        {{ __("Save") }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('body_bottom')
    @parent
@endsection
