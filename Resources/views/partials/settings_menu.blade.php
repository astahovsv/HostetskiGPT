<li @if (Route::is('hostetskigpt.settings')) class="active" @endif>
  <a href="{{ route('hostetskigpt.settings', ['mailbox_id'=>$mailbox->id]) }}">
    <i class="glyphicon glyphicon-globe"></i>GPT Assistant
  </a>
</li>