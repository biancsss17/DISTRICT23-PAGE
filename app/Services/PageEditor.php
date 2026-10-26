<?php

namespace App\Services;

use App\Models\{SiteSetting, PageSection, HeroSlide};

class PageEditor
{
    public static function read(string $key): ?array
    {
        $value = json_decode(SiteSetting::where('key', $key)->value('value') ?? 'null', true);
        return is_array($value) ? $value : null;
    }

    public static function write(string $key, array $data): void
    {
        SiteSetting::updateOrCreate(['key'=>$key], ['value'=>json_encode($data)]);
    }

    public static function current(): array
    {
        $defaults=[];
        foreach (config('cms') as $fields) foreach ($fields as $key=>$field) $defaults[$key]=$field[2];
        $settings=array_replace($defaults,SiteSetting::whereIn('key',array_keys($defaults))->pluck('value','key')->all());
        return ['settings'=>$settings,
            'sections'=>PageSection::where('key','!=','activities')->orderBy('id')->get(['id','key','title','description','sort_order','visible'])->toArray(),
            'slides'=>HeroSlide::orderBy('sort_order')->orderBy('id')->pluck('media_id')->all(),
            'styles'=>self::read('visual_styles') ?? [],
        ];
    }

    public static function preview(): ?array
    {
        return request()->attributes->get('visual_preview');
    }

    public static function fingerprint(array $data): string
    {
        return hash('sha256',json_encode($data));
    }

    public static function css(array $styles): string
    {
        $css='';
        foreach(config('page_editor.targets') as $key=>[$label,$selector]) {
            $style=$styles[$key] ?? [];
            $rules=[];
            $font=config('page_editor.fonts')[$style['font'] ?? 'original'] ?? '';
            if($font) $rules[]='font-family:'.$font.'!important';
            if(is_numeric($style['size']??null)) {
                $size=max(10,min(120,(int)$style['size']));
                $rules[]='font-size:'.($size>32 ? 'clamp(28px,'.($size/14.4).'vw,'.$size.'px)' : $size.'px').'!important';
            }
            if(in_array((string)($style['weight']??''),['300','400','500','600','700','800'])) $rules[]='font-weight:'.$style['weight'].'!important';
            if(in_array($style['align']??'', ['left','center','right'])) $rules[]='text-align:'.$style['align'].'!important';
            if(preg_match('/^#[a-f0-9]{6}$/i',$style['color']??'')) $rules[]='color:'.$style['color'].'!important';
            if($rules) $css.=$selector.'{'.implode(';',$rules).'}';
        }
        $bg=$styles['background']??[];
        if(in_array($bg['mode']??'', ['solid','gradient']) && preg_match('/^#[a-f0-9]{6}$/i',$bg['start']??'') && preg_match('/^#[a-f0-9]{6}$/i',$bg['end']??'')) {
            $value=$bg['mode']==='solid' ? $bg['start'] : 'linear-gradient('.max(0,min(360,(int)($bg['angle']??135))).'deg,'.$bg['start'].','.$bg['end'].')';
            $css.='.home-page{background:'.$value.'!important}.home-page>.site-ribbons{display:none!important}.home-page main>.section,.home-page main>.stats-section{background:transparent!important}';
        }
        if(is_numeric($styles['spacing']??null)) $css.='.home-page main .section{padding-top:'.max(16,min(160,(int)$styles['spacing'])).'px;padding-bottom:'.max(16,min(160,(int)$styles['spacing'])).'px}';
        return $css;
    }
}
