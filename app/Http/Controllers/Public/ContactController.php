<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;

class ContactController extends Controller
{
    public function index()
    {
        return view('public.contact', [
            'showQuoteForm' => SiteContent::bool('show_quote_form'),
        ]);
    }
}
