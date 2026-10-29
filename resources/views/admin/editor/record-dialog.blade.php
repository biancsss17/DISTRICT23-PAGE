<dialog id="record-editor">
    <header>
        <div><b id="record-heading">Manage records</b>
            <p>Record changes save immediately on this installation.</p>
        </div><button id="close-record" type="button" aria-label="Close content editor">Close ×</button>
    </header>
    <div class="record-workspace">
        <nav class="record-kinds"><button type="button" data-record-type="news">News</button><button type="button"
                data-record-type="achievements">Achievements</button><button type="button"
                data-record-type="officers">Officers</button></nav>
        <div id="record-message" role="status"></div>
        <div id="record-list"></div>
        <form id="record-form" enctype="multipart/form-data" hidden></form>
    </div>
</dialog>
