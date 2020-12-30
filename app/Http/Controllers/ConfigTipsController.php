<?php

namespace App\Http\Controllers;

use App\Models\ConfigTips;
use Illuminate\Http\Request;
use League\CommonMark\CommonMarkConverter;

class ConfigTipsController extends Controller
{
    public function store(Request $request)
    {
        $tips = ConfigTips::create([
            'creator' => auth()->user()->user_name,
            'content' => $request->input('content'),
        ]);

        return response()->json([
            'data' => [
                'html' => $this->getCommonMarkConverter()->convertToHtml($tips->content ?? ''),
                'md'   => $tips->content ?? '',
            ],
        ], 201);
    }

    public function getLast()
    {
        $tip = ConfigTips::query()->latest()->value('content');

        return response()->json([
            'data' => [
                'html' => $this->getCommonMarkConverter()->convertToHtml($tip ?? ''),
                'md'   => $tip ?? '',
            ],
        ]);
    }

    /**
     * @return CommonMarkConverter
     *
     * @author duc <1025434218@qq.com>
     */
    protected function getCommonMarkConverter(): CommonMarkConverter
    {
        return new CommonMarkConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
