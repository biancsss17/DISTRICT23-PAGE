(() => {
    'use strict';
    const dialog = document.querySelector('#record-editor');
    const list = document.querySelector('#record-list');
    const form = document.querySelector('#record-form');
    const message = document.querySelector('#record-message');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let type = 'news', id = null, dirty = false, busy = false;
    const labels = {news:'News', achievements:'Achievements', officers:'Officers'};

    async function request(path, method = 'GET', data) {
        const response = await fetch('/admin/editor/' + path, {
            method, credentials:'same-origin',
            headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf,...(data instanceof FormData ? {} : data ? {'Content-Type':'application/json'} : {})},
            body:data instanceof FormData ? data : data ? JSON.stringify(data) : undefined,
        });
        let result;
        try { result = await response.json(); }
        catch { throw new Error('The session expired or the server did not respond. Reload and sign in again.'); }
        if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : result.message || 'Request failed.');
        return result;
    }
    function say(text, error = false) {
        message.textContent = text;
        message.classList.toggle('error', error);
    }
    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text != null) node.textContent = text;
        return node;
    }
    function field(label, name, value = '', kind = 'text', options = {}) {
        const wrapper = element('label','record-field');
        wrapper.append(element('span','',label));
        const input = document.createElement(kind === 'textarea' ? 'textarea' : 'input');
        if (kind !== 'textarea') input.type = kind;
        input.name = name;
        input.value = value ?? '';
        if (options.required) input.required = true;
        if (options.maxLength) input.maxLength = options.maxLength;
        if (options.min != null) input.min = options.min;
        if (options.accept) input.accept = options.accept;
        if (options.multiple) input.multiple = true;
        wrapper.append(input);
        return wrapper;
    }
    function selectField(label, name, value, options) {
        const wrapper = element('label','record-field');
        wrapper.append(element('span','',label));
        const input = document.createElement('select');
        input.name = name;
        options.forEach(([key,text]) => {
            const option = element('option','',text);
            option.value = key;
            input.append(option);
        });
        input.value = value ?? options[0][0];
        wrapper.append(input);
        return wrapper;
    }
    function mediaItems(items, title) {
        if (!items.length) return null;
        const group = element('div','record-media');
        group.append(element('h3','',title));
        items.forEach(item => {
            const tile = element('div','record-media-item');
            const asset = document.createElement(item.video ? 'video' : 'img');
            asset.src = item.url;
            if (item.video) { asset.controls = true; asset.preload = 'metadata'; }
            else asset.alt = item.name;
            tile.append(asset,element('span','',item.name));
            const remove = element('button','record-danger','Delete ' + (item.video ? 'video' : 'photo'));
            remove.type = 'button';
            remove.addEventListener('click',async () => {
                if (!confirm('Permanently delete this file everywhere on the website?')) return;
                try {
                    await request('media/' + item.id,'DELETE');
                    say('Media deleted.');
                    if (id) await edit(id);
                    document.querySelector('#page-preview').src='/admin/editor/preview?editor-preview=1&t='+Date.now();
                } catch (error) { say(error.message,true); }
            });
            tile.append(remove);
            group.append(tile);
        });
        return group;
    }
    async function listing(nextType = type) {
        if (dirty && !confirm('Discard unsaved record changes?')) return;
        type = nextType;
        id = null;
        dirty = false;
        form.hidden = true;
        list.hidden = false;
        document.querySelector('#record-heading').textContent = labels[type];
        document.querySelectorAll('.record-kinds button').forEach(button => button.setAttribute('aria-pressed',String(button.dataset.recordType === type)));
        say('Loading records...');
        try {
            const result = await request('records/' + type);
            list.replaceChildren();
            const create = element('button','record-primary','+ Add ' + (type === 'officers' ? 'officer' : type === 'news' ? 'story' : 'achievement'));
            create.type = 'button';
            create.addEventListener('click',() => edit(null));
            list.append(create);
            if (!result.records.length) list.append(element('p','record-empty','No records yet.'));
            result.records.forEach(record => {
                const row = element('div','record-row');
                const info = element('div','record-row-info');
                info.append(element('strong','',record.title || record.name),element('small','',record.status + (record.position ? ' · '+record.position : '')));
                const editButton = element('button','','Edit');
                editButton.type = 'button';
                editButton.addEventListener('click',() => edit(record.id));
                let preview = null;
                if (type !== 'officers') {
                    preview = element('a','','Preview');
                    preview.href = '/admin/editor/records/'+type+'/'+record.id+'/preview';
                    preview.target = '_blank';
                    preview.rel = 'noopener';
                }
                const remove = element('button','record-danger','Delete');
                remove.type = 'button';
                remove.addEventListener('click',async () => {
                    if (!confirm('Permanently delete '+(record.title || record.name)+'?')) return;
                    try {
                        await request('records/'+type+'/'+record.id,'DELETE');
                        await listing(type);
                        say('Record deleted.');
                        document.querySelector('#page-preview').src='/admin/editor/preview?editor-preview=1&t='+Date.now();
                    } catch (error) { say(error.message,true); }
                });
                row.append(info,editButton,...(preview ? [preview] : []),remove);
                list.append(row);
            });
            say('');
        } catch (error) { say(error.message,true); }
    }
    async function edit(nextId) {
        if (dirty && !confirm('Discard unsaved record changes?')) return;
        id = nextId;
        dirty = false;
        let record = {};
        if (id) {
            try { record = (await request('records/'+type+'/'+id)).record; }
            catch (error) { say(error.message,true); return; }
        }
        list.hidden = true;
        form.hidden = false;
        form.replaceChildren();
        document.querySelector('#record-heading').textContent = (id ? 'Edit ' : 'Add ') + (type === 'officers' ? 'officer' : type === 'news' ? 'News story' : 'achievement');
        const back = element('button','record-back','← All '+labels[type]);
        back.type = 'button';
        back.addEventListener('click',() => listing(type));
        form.append(back);
        const grid = element('div','record-grid');
        const main = element('section','record-column');
        const side = element('section','record-column');
        if (type === 'officers') {
            main.append(field('Name','name',record.name,'text',{required:true,maxLength:160}));
            main.append(field('Position','position',record.position,'text',{required:true,maxLength:160}));
            main.append(field('Term','term',record.term,'text',{maxLength:160}));
            main.append(field('Biography','biography',record.biography,'textarea',{maxLength:10000}));
            main.append(field('Email','email',record.email,'email'));
            main.append(field('Social URL','social_url',record.social_url,'url'));
            side.append(field('Portrait','photo_upload','','file',{accept:'image/jpeg,image/png,image/webp,image/gif'}));
        } else {
            main.append(field('Title','title',record.title,'text',{required:true,maxLength:180}));
            main.append(field('Slug','slug',record.slug,'text',{required:true,maxLength:180}));
            main.append(field('Summary','excerpt',record.excerpt,'textarea',{maxLength:600}));
            main.append(field('Content','content',record.content,'textarea',{required:true,maxLength:100000}));
            side.append(field('Category','category',record.category,'text',{maxLength:80}));
            side.append(field('Published at','published_at',record.published_at || new Date().toISOString().slice(0,16),'datetime-local'));
            if (type === 'achievements') side.append(field('Achievement date','event_date',record.event_date,'date'));
            const featured = element('label','record-check');
            const check = document.createElement('input');
            check.type = 'checkbox'; check.name = 'featured'; check.value = '1'; check.checked = !!record.featured;
            featured.append(check,document.createTextNode('Feature this story'));
            side.append(featured);
            side.append(field('Cover photo or video','cover_upload','','file',{accept:'image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg'}));
        }
        side.append(selectField('Status','status',record.status,[['draft','Draft'],['published','Published']]));
        side.append(field('Sort order','sort_order',record.sort_order ?? 0,'number',{required:true,min:0}));
        side.append(field('Additional photos and video','gallery_uploads[]','','file',{accept:'image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg',multiple:true}));
        side.append(element('p','record-hint','Maximum 3 photos and 1 video total per item, including its cover or portrait. 10 MB per photo and 50 MB per video. New gallery uploads replace the current gallery.'));
        if (record.cover) side.append(mediaItems([record.cover],type === 'officers' ? 'Portrait' : 'Cover'));
        if (record.gallery?.length) side.append(mediaItems(record.gallery,'Gallery'));
        grid.append(main,side);
        form.append(grid);
        const save = element('button','record-primary','Save '+(type === 'officers' ? 'officer' : 'story'));
        save.type = 'submit';
        form.append(save);
        const titleInput = form.querySelector('[name="title"]'), slugInput = form.querySelector('[name="slug"]');
        if (titleInput && slugInput && !id) {
            let automatic = true;
            slugInput.addEventListener('input',() => automatic = false);
            titleInput.addEventListener('input',() => {
                if (automatic) slugInput.value = titleInput.value.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
            });
        }
        say('');
        form.scrollIntoView({block:'start'});
    }
    form.addEventListener('input',() => dirty = true);
    form.addEventListener('submit',async event => {
        event.preventDefault();
        if (busy) return;
        busy = true;
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        say('Saving...');
        try {
            const result = await request('records/'+type+(id ? '/'+id : ''),'POST',new FormData(form));
            dirty = false;
            id = result.record.id;
            say(result.message);
            await edit(id);
            say(result.message);
            document.querySelector('#page-preview').src='/admin/editor/preview?editor-preview=1&t='+Date.now();
        } catch (error) { say(error.message,true); }
        finally { busy = false; button.disabled = false; }
    });
    function close(event) {
        if (dirty && !confirm('Discard unsaved record changes?')) { event?.preventDefault(); return; }
        dialog.close();
        document.querySelector('#page-preview').src='/admin/editor/preview?editor-preview=1&t='+Date.now();
    }
    document.querySelector('#close-record').addEventListener('click',close);
    dialog.addEventListener('cancel',event => { event.preventDefault(); close(event); });
    document.querySelectorAll('[data-record-type]').forEach(button => button.addEventListener('click',() => {
        if (!dialog.open) dialog.showModal();
        listing(button.dataset.recordType);
    }));
    document.querySelectorAll('[data-recent-type]').forEach(button => button.addEventListener('click',() => {
        if (!dialog.open) dialog.showModal();
        type = button.dataset.recentType;
        edit(Number(button.dataset.recentId));
    }));
    window.D23Records = {open(nextType,nextId) {
        if (!labels[nextType]) return;
        if (!dialog.open) dialog.showModal();
        type = nextType;
        if (nextId) edit(Number(nextId));
        else listing(nextType);
    }};
})();
