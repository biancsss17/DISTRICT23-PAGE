<div id="media-panel" role="tabpanel" class="tab-panel" hidden>
    <h2>Homepage cover</h2>
    <p>Choose up to 20 slides. Use the arrows to reorder them. Removing a slide here does not delete its file.</p>
    <div id="selected-slides"></div><label class="upload-button">+ Upload photos or videos<input id="media-upload"
            type="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg"
            multiple></label>
    <p>{{ $uploadLimits['photoMb'] }} MB per photo and {{ $uploadLimits['videoMb'] }} MB per video. Uploads are stored
        immediately; they appear on the homepage only after publishing.</p>
    <h2>Media library</h2>
    <div id="media-library"></div>
    <p>Delete removes a stored file from every use immediately.</p>
</div>
