<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\HeroSlide;
use App\Models\Media;
use App\Models\Officer;
use App\Models\PageSection;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('cms') as $group) {
            foreach ($group as $key => $field) {
                SiteSetting::firstOrCreate(['key' => $key], ['value' => $field[2]]);
            }
        }
        foreach ([
            ['heritage', 'A legacy that continues.', 'Knowledge, love and a shared sense of purpose.'],
            ['statistics', 'Our community, in perspective.', 'A growing story of participation.'],
            ['news', 'Stories that bring us together.', 'The latest from our community.'],
            ['achievements', 'Small steps. Lasting impact.', 'Celebrating milestones that define our journey.'],
            ['officers', 'People behind the purpose.', 'Meet the people helping our community move forward.'],
        ] as $i => $s) {
            PageSection::firstOrCreate(['key' => $s[0]], ['title' => $s[1], 'description' => $s[2], 'sort_order' => $i]);
        }
        $samples = [
            'news' => ['A new chapter for our community', 'Making space for meaningful conversations', 'Looking forward, together'],
            'achievements' => ['Recognizing the spirit of service', 'A milestone in shared learning', 'Celebrating community participation'],
        ];
        foreach ($samples as $type => $titles) {
            foreach ($titles as $i => $title) {
                Content::firstOrCreate(['slug' => Str::slug($title)], [
                    'type' => $type, 'title' => $title, 'excerpt' => 'Sample content • An invitation to connect, share ideas and build a stronger community.',
                    'content' => "This is sample content for the UECFI website. It does not describe a verified organizational event or achievement.\n\nAn administrator can replace this story, add an approved photograph and publish the details through the content manager.",
                    'category' => 'Sample content', 'status' => 'published', 'featured' => $i === 0, 'published_at' => now()->subDays($i + 1), 'sort_order' => $i,
                    'event_date' => $type === 'news' ? null : now()->toDateString(), 'location' => null,
                ]);
            }
        }
        foreach (['President', 'Vice President', 'Secretary', 'Treasurer'] as $i => $position) {
            Officer::firstOrCreate(['name' => 'Sample officer '.($i + 1)], ['position' => $position, 'biography' => 'Sample profile. Replace with an approved officer biography and portrait.', 'term' => 'Sample term', 'hierarchy_level' => $i < 2 ? $i : 2, 'sort_order' => $i, 'status' => 'published']);
        }

        $sampleImages = [
            'sample-community-circle.png' => 'Community learning circle',
            'sample-service-recognition.png' => 'Service recognition',
            'sample-mountain-sunrise.png' => 'Mountain sunrise',
            'sample-uecfi-slideshow.webm' => 'Sample UECFI slideshow video',
        ];
        $media = [];
        foreach ($sampleImages as $filename => $name) {
            $path = 'cms/'.$filename;
            $isVideo = str_ends_with($filename, '.webm');
            $image = Media::firstOrCreate(['path' => $path], ['name' => $name, 'alt' => $isVideo ? 'Sample UECFI slideshow video' : $name.' sample photograph']);
            if (! Storage::disk('public')->exists($path) && is_file(public_path('images/'.$filename))) {
                Storage::disk('public')->put($path, file_get_contents(public_path('images/'.$filename)));
            }
            $media[$filename] = $image;
        }
        $sampleStories = [
            ['news', 'A new chapter for our community', 'sample-community-circle.png', ['sample-service-recognition.png', 'sample-uecfi-slideshow.webm']],
            ['achievements', 'Recognizing the spirit of service', 'sample-service-recognition.png', ['sample-community-circle.png', 'sample-uecfi-slideshow.webm']],
        ];
        foreach ($sampleStories as [$type, $title, $cover, $galleryFiles]) {
            $story = Content::where('type', $type)->where('slug', Str::slug($title))->first();
            if ($story) {
                $story->update(['cover_id' => $media[$cover]->id]);
                $story->gallery()->sync(collect($galleryFiles)->mapWithKeys(fn ($filename, $index) => [
                    $media[$filename]->id => ['sort_order' => $index],
                ])->all());
            }
        }
        Officer::where('name', 'Sample officer 1')->update(['photo_id' => $media['sample-service-recognition.png']->id]);
        HeroSlide::firstOrCreate(['media_id' => $media['sample-mountain-sunrise.png']->id], ['sort_order' => 0]);
        HeroSlide::firstOrCreate(['media_id' => $media['sample-community-circle.png']->id], ['sort_order' => 1]);
    }
}
