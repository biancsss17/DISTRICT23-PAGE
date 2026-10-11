<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\HeroSlide;
use App\Models\Media;
use App\Models\Officer;
use App\Models\User;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password-123')]);
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    private function payload(array $extra = []): array
    {
        return array_replace(['title' => 'A real test story', 'slug' => 'test-story', 'content' => 'Public content from the CMS.', 'excerpt' => 'Test excerpt', 'status' => 'draft', 'sort_order' => 0, 'published_at' => now()->format('Y-m-d H:i:s')], $extra);
    }

    public function test_public_pages_details_and_missing_pages(): void
    {
        foreach (['/', '/news', '/achievements', '/activities', '/officers', '/admin/login'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (Content::all() as $record) {
            $this->get('/'.$record->type.'/'.$record->slug)->assertOk()->assertSee($record->title);
        }
        $this->get('/news/missing')->assertNotFound();
        $this->get('/missing')->assertNotFound();
    }

    public function test_guests_and_non_admins_cannot_access_cms(): void
    {
        foreach (['/admin', '/admin/content/news', '/admin/settings/home', '/admin/officers'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->post('/admin/content/news', $this->payload())->assertForbidden();
    }

    public function test_login_failure_success_and_logout(): void
    {
        $user = $this->admin();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'correct-password-123'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk();
        $this->post('/admin/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_content_crud_publication_preview_and_type_boundaries(): void
    {
        $this->actingAs($this->admin());
        foreach (['news', 'achievements', 'activities'] as $type) {
            $slug = 'created-'.$type;
            $this->post('/admin/content/'.$type, $this->payload(['slug' => $slug]))->assertSessionHasNoErrors()->assertRedirect();
            $record = Content::where('slug', $slug)->firstOrFail();
            $this->get('/'.$type.'/'.$slug)->assertNotFound();
            $this->get('/admin/content/'.$type.'/'.$record->id.'/preview')->assertOk()->assertSee('Private preview');
            $this->put('/admin/content/'.$type.'/'.$record->id, $this->payload(['slug' => $slug, 'status' => 'published']))->assertSessionHasNoErrors();
            $this->get('/'.$type.'/'.$slug)->assertOk()->assertSee('Public content from the CMS.');
            $this->put('/admin/content/'.$type.'/'.$record->id, $this->payload(['slug' => $slug, 'status' => 'published', 'published_at' => now()->addDay()->format('Y-m-d H:i:s')]))->assertSessionHasNoErrors();
            $this->get('/'.$type.'/'.$slug)->assertNotFound();
            $other = $type === 'news' ? 'activities' : 'news';
            $this->get('/admin/content/'.$other.'/'.$record->id.'/edit')->assertNotFound();
            $this->delete('/admin/content/'.$type.'/'.$record->id)->assertRedirect();
            $this->assertDatabaseMissing('contents', ['id' => $record->id]);
        }
    }

    public function test_admin_forms_render_and_validation_rejects_bad_data(): void
    {
        $this->actingAs($this->admin());
        foreach (['/admin', '/admin/content/news', '/admin/content/news/create', '/admin/officers', '/admin/officers/create'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (array_keys(config('cms')) as $group) {
            $this->get('/admin/settings/'.$group)->assertOk();
        }
        $record = Content::first();
        $this->get('/admin/content/'.$record->type.'/'.$record->id.'/edit')->assertOk();
        $this->get('/admin/officers/'.Officer::first()->id.'/edit')->assertOk();
        $this->post('/admin/content/news', [])->assertSessionHasErrors(['title', 'slug', 'content', 'status', 'sort_order']);
        $this->post('/admin/content/news', $this->payload(['slug' => '<script>']))->assertSessionHasErrors('slug');
        $this->post('/admin/content/news', $this->payload(['status' => 'published', 'published_at' => '']))->assertSessionHasErrors('published_at');
    }

    public function test_images_upload_directly_to_content_and_officers(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $bytes = file_get_contents(public_path('images/uecfi-logo.png'));
        $cover = UploadedFile::fake()->createWithContent('cover.png', $bytes);
        $gallery = UploadedFile::fake()->createWithContent('gallery.png', $bytes);
        $this->post('/admin/content/activities', $this->payload([
            'slug' => 'uploaded-activity',
            'title' => 'Uploaded activity',
            'cover_upload' => $cover,
            'gallery_uploads' => [$gallery],
        ]))->assertSessionHasNoErrors();
        $record = Content::where('slug', 'uploaded-activity')->firstOrFail();
        $this->assertNotNull($record->cover_id);
        $this->assertCount(1, $record->gallery);
        Storage::disk('public')->assertExists($record->cover->path);
        Storage::disk('public')->assertExists($record->gallery->first()->path);
        $this->get('/media/'.$record->cover_id)->assertOk();
        $this->get('/activities/'.$record->slug)->assertNotFound();

        $portrait = UploadedFile::fake()->createWithContent('portrait.png', $bytes);
        $this->post('/admin/officers', ['name' => 'Uploaded officer', 'position' => 'Coordinator', 'biography' => 'Biography', 'status' => 'published', 'hierarchy_level' => 3, 'sort_order' => 2, 'photo_upload' => $portrait])->assertSessionHasNoErrors();
        $officer = Officer::where('name', 'Uploaded officer')->firstOrFail();
        $this->assertNotNull($officer->photo_id);
        Storage::disk('public')->assertExists($officer->photo->path);
    }

    public function test_admin_can_delete_photo_and_video_and_all_their_references(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $record = Content::firstOrFail();

        $photo = Media::create(['path' => 'cms/delete-photo.png', 'name' => 'delete-photo.png', 'alt' => 'Test photo']);
        $video = Media::create(['path' => 'cms/delete-video.mp4', 'name' => 'delete-video.mp4', 'alt' => 'Test video']);
        Storage::disk('public')->put($photo->path, 'photo bytes');
        Storage::disk('public')->put($video->path, 'video bytes');
        $record->update(['cover_id' => $photo->id]);
        $record->gallery()->attach([$photo->id => ['sort_order' => 0], $video->id => ['sort_order' => 1]]);
        HeroSlide::create(['media_id' => $video->id, 'sort_order' => 0]);

        $this->delete('/admin/settings/home/media/'.$photo->id)->assertRedirect();
        $this->assertDatabaseMissing('media', ['id' => $photo->id]);
        $this->assertDatabaseMissing('activity_images', ['content_id' => $record->id, 'media_id' => $photo->id]);
        $this->assertNull($record->fresh()->cover_id);
        Storage::disk('public')->assertMissing($photo->path);

        $this->delete('/admin/content/'.$record->type.'/'.$record->id.'/media/'.$video->id)->assertRedirect();
        $this->assertDatabaseMissing('media', ['id' => $video->id]);
        $this->assertDatabaseMissing('activity_images', ['content_id' => $record->id, 'media_id' => $video->id]);
        $this->assertDatabaseMissing('hero_slides', ['media_id' => $video->id]);
        Storage::disk('public')->assertMissing($video->path);
    }

    public function test_officer_crud_and_hierarchy(): void
    {
        $this->actingAs($this->admin());
        $data = ['name' => 'New officer', 'position' => 'Coordinator', 'term' => '2026–2028', 'biography' => 'Approved biography', 'status' => 'draft', 'hierarchy_level' => 3, 'sort_order' => 2];
        $this->post('/admin/officers', $data)->assertSessionHasNoErrors();
        $officer = Officer::where('name', 'New officer')->firstOrFail();
        $this->assertSame('2026–2028', $officer->term);
        $this->get('/officers')->assertDontSee('New officer');
        $this->put('/admin/officers/'.$officer->id, array_replace($data, ['status' => 'published', 'term' => '2027–2029']))->assertSessionHasNoErrors();
        $officer->refresh();
        $this->assertSame('2027–2029', $officer->term);
        $this->get('/officers')->assertSee('New officer')->assertSee('Approved biography')->assertSee('2027–2029');
        $this->delete('/admin/officers/'.$officer->id)->assertRedirect();
        $this->assertDatabaseMissing('officers', ['id' => $officer->id]);
    }

    public function test_settings_change_public_content_and_unsafe_links_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $data = [];
        foreach (config('cms.home') as $key => $field) {
            $data[$key] = $field[2];
        }
        $data['hero_heading'] = 'Together we lead.';
        $this->put('/admin/settings/home', $data)->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Together we lead.');
        $this->put('/admin/settings/home', array_replace($data, ['primary_url' => 'javascript:alert(1)']))->assertSessionHasErrors('primary_url');
        $this->put('/admin/settings/home', array_replace($data, ['primary_url' => '//evil.example']))->assertSessionHasErrors('primary_url');
        $this->put('/admin/settings/brand', ['primary_color' => 'red; color:red', 'secondary_color' => '#ffffff', 'accent_color' => '#000000'])->assertSessionHasErrors('primary_color');
    }

    public function test_escaped_content_and_original_form(): void
    {
        $record = Content::first();
        $record->content = '<script>alert("unsafe")</script>';
        $record->save();
        $this->get('/'.$record->type.'/'.$record->slug)->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert', false);
        $this->post('/generate', ['business_type' => 'Coffee shop'])->assertOk()->assertSee('Generated successfully');
        $this->post('/generate',[])->assertSessionHasErrors('business_type');
    }
}
