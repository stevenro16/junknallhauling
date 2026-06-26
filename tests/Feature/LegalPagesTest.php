<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_has_legal_links_and_required_sms_disclosures(): void
    {
        $res = $this->get('/');

        $res->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Terms &amp; Conditions', false)
            // Required SMS/A2P disclosures in the privacy policy.
            ->assertSee('Message and data rates may apply', false)
            ->assertSee('Message frequency varies', false)
            // Mobile-number non-sharing statement.
            ->assertSee('share, sell, rent, or trade your mobile phone number', false);
    }
}
