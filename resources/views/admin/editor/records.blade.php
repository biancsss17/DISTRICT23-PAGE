<div id="records-panel" role="tabpanel" class="tab-panel" hidden>
    <h2>Stories & people</h2>
    <p>Create, edit and remove records here. These changes save directly and do not wait for page publication.</p>
    <div class="record-summary">
        @foreach ($counts as $label => $count)
            <div><strong>{{ $count }}</strong><span>{{ ucfirst($label) }} published</span></div>
        @endforeach
    </div>
    <button class="record-launch" data-record-type="news">Manage News</button><button class="record-launch"
        data-record-type="achievements">Manage Achievements</button><button class="record-launch"
        data-record-type="officers">Manage Officers</button><button class="record-launch"
        data-record-type="songs">Manage Songs</button><button class="record-launch"
        data-record-type="centers">Manage Map CENTRO</button>
    <h3>Recent updates</h3>
    @forelse($recent as $record)
        <button class="record-recent" data-recent-type="{{ $record->type }}"
            data-recent-id="{{ $record->id }}"><span>{{ $record->title }}</span><small>{{ ucfirst($record->type) }} ·
            {{ $record->status }}</small></button>@empty<p>No updates yet.</p>
    @endforelse
</div>
