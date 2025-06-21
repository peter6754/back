@if($entry->status === 'failed')
    <a href="{{ route('mail-queue.resend', $entry->id) }}"
       class="btn btn-sm btn-outline-warning"
       onclick="return confirm('Переотправить это письмо?')">
        <i class="fa fa-redo"></i> Переотправить
    </a>
@endif
