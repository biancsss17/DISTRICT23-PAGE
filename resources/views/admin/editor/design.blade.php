<div id="design-panel" role="tabpanel" class="tab-panel" hidden>
    <label class="field">Style this part<select id="style-target">
            @foreach ($editorConfig['targets'] as $key => $target)
                <option value="{{ $key }}">{{ $target[0] }}</option>
            @endforeach
        </select>
    </label>
    <label class="field">Font<select data-style="font">
            @foreach ($editorConfig['fonts'] as $name => $stack)
                <option value="{{ $name }}">{{ $name === 'original' ? 'Keep original font' : $name }}</option>
            @endforeach
        </select>
    </label>
    <div class="field-grid"><label class="field">Size (px)<input data-style="size" type="number" min="10"
                max="120" placeholder="Original"></label><label class="field">Weight<select data-style="weight">
                <option value="">Original</option>
                @foreach ([300, 400, 500, 600, 700, 800] as $weight)
                    <option>{{ $weight }}</option>
                @endforeach
            </select></label></div>
    <label class="field">Alignment<select data-style="align">
            <option value="">Original</option>
            <option>left</option>
            <option>center</option>
            <option>right</option>
        </select></label><label class="field">Text color<input data-style="color" type="color"></label><button
        id="reset-style">Reset this part to original</button>
    <hr>
    <h2>Continuous page background</h2>
    <p>The cover media and footer keep their own backgrounds.</p><label class="field">Background<select
            data-background="mode">
            <option value="original">Original design</option>
            <option value="solid">Solid color</option>
            <option value="gradient">Gradient</option>
        </select></label>
    <div class="field-grid"><label class="field">Start<input type="color" data-background="start"></label><label
            class="field">End<input type="color" data-background="end"></label></div><label class="field">Gradient
        angle<input type="range" min="0" max="360" data-background="angle"><output
            id="angle-value"></output></label><label class="field">Section spacing (px)<input type="number"
            id="section-spacing" min="16" max="160" placeholder="Original spacing"></label>
    <p>Fonts use installed system families, so no external font service is required. Always check phone size and both
        website themes.</p>
</div>
