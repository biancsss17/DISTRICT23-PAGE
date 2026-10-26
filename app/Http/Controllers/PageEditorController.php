<?php

namespace App\Http\Controllers;

use App\Models\{SiteSetting, PageSection, HeroSlide, Media, Content, Officer};
use App\Services\{PageEditor, MediaStorage};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PageEditorController extends Controller
{
    public function edit()
    {
        $draft=PageEditor::read('visual_draft');
        return response()->view('admin.page-editor', [
            'state'=>$draft['page'] ?? PageEditor::current(),
            'revision'=>$draft['revision'] ?? '',
            'groups'=>config('cms'), 'editorConfig'=>config('page_editor'),
            'media'=>Media::latest()->get()->map(fn($m)=>['id'=>$m->id,'name'=>$m->name,'url'=>$m->url,'video'=>$m->is_video]),
            'counts'=>[
                'news'=>Content::where('type','news')->published()->count(),
                'achievements'=>Content::where('type','achievements')->published()->count(),
                'officers'=>Officer::where('status','published')->count(),
            ],
            'recent'=>Content::whereIn('type',['news','achievements'])->latest('updated_at')->limit(6)->get(),
        ])->withHeaders(['Cache-Control'=>'no-store']);
    }

    public function preview(Request $request)
    {
        $request->attributes->set('visual_preview', PageEditor::read('visual_draft')['page'] ?? PageEditor::current());
        return response(app(PublicController::class)->home())->header('Cache-Control','no-store');
    }

    public function save(Request $request)
    {
        $rules=['revision'=>'present|nullable|string','settings'=>'required|array','sections'=>'required|array','sections.*.id'=>['required','distinct',Rule::exists('page_sections','id')->where(fn($q)=>$q->where('key','!=','activities'))], 'sections.*.title'=>'required|string|max:180','sections.*.description'=>'nullable|string|max:1000','sections.*.sort_order'=>'required|integer|min:0|max:1000','sections.*.visible'=>'required|boolean','slides'=>'present|array|max:20','slides.*'=>'integer|distinct|exists:media,id','styles'=>'present|array'];
        foreach(config('cms') as $fields) foreach($fields as $key=>$field) {
            $rules['settings.'.$key]=match($field[1]) {
                'color'=>['required','regex:/^#[a-f0-9]{6}$/i'], 'number'=>'required|integer|min:0|max:9999999',
                'media'=>['nullable',Rule::exists('media','id')], 'email'=>'nullable|email|max:255', 'url'=>'nullable|url:http,https|max:255',
                'path'=>['required','regex:~^/(?!/)[a-zA-Z0-9/_#?=&%.-]*$~','max:255'],
                default=>'present|nullable|string|max:5000',
            };
        }
        foreach(['hero_heading','hero_kicker','header_title','primary_text','secondary_text'] as $key) $rules['settings.'.$key]='required|string|max:5000';
        foreach(config('page_editor.targets') as $key=>$target) {
            $rules['styles.'.$key]='sometimes|array:font,size,weight,align,color';
            $rules['styles.'.$key.'.font']=['nullable',Rule::in(array_keys(config('page_editor.fonts')))];
            $rules['styles.'.$key.'.size']='nullable|integer|min:10|max:120';
            $rules['styles.'.$key.'.weight']=['nullable',Rule::in(['300','400','500','600','700','800'])];
            $rules['styles.'.$key.'.align']=['nullable',Rule::in(['left','center','right'])];
            $rules['styles.'.$key.'.color']=['nullable','regex:/^#[a-f0-9]{6}$/i'];
        }
        $rules['styles.background']='sometimes|array:mode,start,end,angle';
        $rules['styles.background.mode']=['nullable',Rule::in(['original','solid','gradient'])];
        foreach(['start','end'] as $key) $rules['styles.background.'.$key]=['nullable','regex:/^#[a-f0-9]{6}$/i'];
        $rules['styles.background.angle']='nullable|integer|min:0|max:360';
        $rules['styles.spacing']='nullable|integer|min:16|max:160';
        $data=$request->validate($rules);
        $existing=PageEditor::read('visual_draft');
        abort_if(($existing['revision']??'') !== ($data['revision']??''),409,'This draft changed in another window. Reload before editing.');
        unset($data['revision']);
        $keys=collect(config('cms'))->flatMap(fn($f)=>array_keys($f))->all();
        $data['settings']=array_intersect_key($data['settings'],array_flip($keys));
        $data['styles']=array_intersect_key($data['styles'] ?? [],array_flip([...array_keys(config('page_editor.targets')),'background','spacing']));
        $data['sections']=array_map(fn($s)=>array_intersect_key($s,array_flip(['id','title','description','sort_order','visible'])), $data['sections']);
        foreach ($data['sections'] as &$section) $section['key']=PageSection::findOrFail($section['id'])->key;
        unset($section);
        abort_unless(count($data['sections'])===PageSection::where('key','!=','activities')->count(),422,'Include all homepage sections.');
        $revision=bin2hex(random_bytes(16));
        PageEditor::write('visual_draft',['page'=>$data,'revision'=>$revision,'base'=>$existing['base']??PageEditor::fingerprint(PageEditor::current())]);
        return response()->json(['revision'=>$revision,'message'=>'Draft saved. Public page unchanged.']);
    }

    private function apply(array $page): void
    {
        foreach($page['settings'] as $key=>$value) SiteSetting::updateOrCreate(['key'=>$key],['value'=>$value]);
        PageEditor::write('visual_styles',$page['styles']);
        foreach($page['sections'] as $section) {
            $id=$section['id']; unset($section['id'],$section['key']);
            PageSection::whereKey($id)->where('key','!=','activities')->update($section);
        }
        HeroSlide::query()->delete();
        foreach($page['slides'] as $order=>$id) HeroSlide::create(['media_id'=>$id,'sort_order'=>$order]);
        SiteSetting::updateOrCreate(['key'=>'hero_image_id'],['value'=>null]);
    }

    public function publish(Request $request)
    {
        $draft=PageEditor::read('visual_draft');
        abort_unless($draft,422,'Save a draft first.');
        abort_unless($request->input('revision')===$draft['revision'],409,'Draft changed. Reload before publishing.');
        abort_unless($draft['base']===PageEditor::fingerprint(PageEditor::current()),409,'Public settings changed outside this editor. Reset the draft to the current page before continuing.');
        abort_unless(Media::whereIn('id',$draft['page']['slides'])->count()===count($draft['page']['slides']),422,'A selected slide was deleted. Update the media selection.');
        DB::transaction(function() use($draft) {
            PageEditor::write('visual_previous',PageEditor::current());
            $this->apply($draft['page']);
            SiteSetting::where('key','visual_draft')->delete();
        });
        return response()->json(['message'=>'Published on this installation.']);
    }

    public function reset()
    {
        SiteSetting::where('key','visual_draft')->delete();
        return response()->json(['message'=>'Draft discarded. Current public page unchanged.']);
    }

    public function restore()
    {
        $page=PageEditor::read('visual_previous');
        abort_unless($page,422,'No previous publication is available.');
        abort_unless(Media::whereIn('id',$page['slides'])->count()===count($page['slides']),422,'Previous media was deleted. Restore those files before restoring this version.');
        DB::transaction(function() use($page) {
            $this->apply($page);
            SiteSetting::where('key','visual_draft')->delete();
        });
        return response()->json(['message'=>'Previous page settings restored.']);
    }

    public function upload(Request $request)
    {
        $request->validate(['file'=>'required|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg|max:51200']);
        $file=$request->file('file');
        $m=app(MediaStorage::class)->store($file,$file->getClientOriginalName());
        return response()->json(['id'=>$m->id,'name'=>$m->name,'url'=>$m->url,'video'=>$m->is_video]);
    }
}
