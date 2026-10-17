{{--
    A file list. Every link goes through route('files.download'), which
    authorizes via OrderFilePolicy — the storage disk is private and has no
    public URL.

    Expects: $title, $files (Collection<OrderFile>), $empty
--}}
<div class="card mb-3">
    <div class="card-header">
        <h2 class="h6 mb-0">{{ $title }}</h2>
        @if ($files->isNotEmpty())
            <span class="oms-badge oms-badge--pending">{{ $files->count() }}</span>
        @endif
    </div>
    <div class="card-body p-3 p-md-4">
        @if ($files->isEmpty())
            <p class="text-muted mb-0">{{ $empty }}</p>
        @else
            <ul class="oms-filelist mb-0">
                @foreach ($files as $file)
                    <li>
                        <i class="fa-regular fa-file"></i>
                        <a href="{{ route('files.download', $file) }}">{{ $file->original_name }}</a>
                        <span class="oms-meter__label">{{ $file->human_size }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
