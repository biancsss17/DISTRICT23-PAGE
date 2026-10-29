<div id="content-panel" role="tabpanel" class="tab-panel">
    @foreach ($groups as $group => $fields)
        <details data-group="{{ $group }}" @if ($group === 'home') open @endif>
            <summary>{{ $group === 'organization' ? 'Header & contact details' : ucfirst($group) }}</summary>
            @foreach ($fields as $key => $field)
                <label class="field" for="field-{{ $key }}">{{ $field[0] }}
                    @if ($field[1] === 'media')
                        <select id="field-{{ $key }}" data-setting="{{ $key }}">
                            <option value="">Default emblem</option>
                            @foreach ($media as $item)
                                @if (!$item['video'])
                                    <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    @elseif($field[1] === 'textarea')
                        <textarea id="field-{{ $key }}" data-setting="{{ $key }}" rows="3"></textarea>
                    @else<input id="field-{{ $key }}" data-setting="{{ $key }}"
                            type="{{ in_array($field[1], ['number', 'color', 'email', 'url']) ? $field[1] : 'text' }}"
                            @if ($field[1] === 'number') min="0" @endif>
                    @endif
                </label>
            @endforeach
        </details>
    @endforeach
    <p>News, achievements and officer profiles are managed in the Records tab. Their saves apply directly, separately
        from the page draft.</p>
</div>
