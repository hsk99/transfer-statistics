<?php

namespace app\index\controller;

use think\facade\Db;
use app\common\service\Elasticsearch;

class Search
{
    /**
     * 页面
     *
     * @author HSK
     * @date 2022-07-14 09:09:37
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function index(\support\Request $request)
    {
        $keyword = request()->input('keyword', '');

        if (config('elasticsearch.enable', false)) {
            try {
                $response = Elasticsearch::indicesStats(config('elasticsearch.index', 'tracing'));

                $esStats = [
                    'docs'   => $response['indices'][config('elasticsearch.index', 'tracing')]['primaries']['docs']['count'],
                    'store'  => byte_size($response['indices'][config('elasticsearch.index', 'tracing')]['primaries']['store']['size_in_bytes']),
                    'search' => $response['indices'][config('elasticsearch.index', 'tracing')]['primaries']['search']['query_total'],
                ];
            } catch (\Throwable $th) {
                \Hsk99\WebmanException\RunException::report($th);
                $esStats = [
                    'docs'   => null,
                    'store'  => null,
                    'search' => null,
                ];
            }
        } else {
            $esStats = [
                'docs'   => null,
                'store'  => null,
                'search' => null,
            ];
        }

        return view('search/index', [
            'keyword' => $keyword,
            'esStats' => $esStats
        ]);
    }

    /**
     * 搜索
     *
     * @author HSK
     * @date 2022-07-14 13:58:25
     *
     * @param \support\Request $request
     *
     * @return \support\Response
     */
    public function search(\support\Request $request)
    {
        $page    = (int)request()->input('page', 0);
        $limit   = (int)request()->input('limit', 10);
        $keyword = request()->input('keyword', '');

        $page = (1 === $page) ? 0 : $page;

        if (empty($keyword)) {
            return api([], 400, '请输入搜索信息');
        }

        switch (true) {
            case config('elasticsearch.enable', false):
                return static::esSearch($page, $limit, $keyword);
                break;
            default:
                return static::dbSearch($page, $limit, $keyword);
                break;
        }
    }

    /**
     * 使用elasticsearch搜索
     *
     * @author HSK
     * @date 2022-07-14 16:29:29
     *
     * @param int $page
     * @param int $limit
     * @param string $keyword
     *
     * @return void
     */
    protected static function esSearch($page, $limit, $keyword)
    {
        try {
            $startTIme = microtime(true);

            $highlightFields = [];
            $_source = ['day', 'time', 'trace', 'project', 'ip', 'transfer', 'code', 'details'];
            foreach ($_source as $source) {
                $highlightFields[$source] = ['number_of_fragments' => 3];
            }
            $params = [
                "from"             => $page,
                "size"             => $limit,
                'index'            => config('elasticsearch.index', 'tracing'),
                'track_total_hits' => true,
                '_source'          => $_source,
                'body'             => [
                    'query' => [
                        'multi_match' => [
                            'query'  => $keyword,
                            'fields' => ['time', 'project', 'ip', 'transfer', 'details']
                        ]
                    ],
                    'sort' => [
                        ['_score' => ['order' => 'desc']],
                        ['sort' => ['order' => 'desc']]
                    ],
                    'highlight' => [
                        'number_of_fragments' => 3,
                        'fragment_size'       => 1000,
                        'max_analyzed_offset' => 1000000,
                        'fields'              => $highlightFields,
                        'pre_tags'            => ["<em style='color:red;font-size: 18px !important;'>"],
                        'post_tags'           => ["</em>"]
                    ]
                ]
            ];
            $response = Elasticsearch::search($params);

            if (empty($response['hits'])) {
                return api([], 400, '搜索异常');
            }

            $data = [];
            foreach ($response['hits']['hits'] as $item) {
                foreach ($_source as $source) {
                    if (!empty($item['highlight'][$source])) {
                        ${$source} = implode("\n", $item['highlight'][$source]);
                    } else {
                        ${$source} = $item['_source'][$source];
                    }
                }
                $data[] = [
                    'day'      => $day,
                    'time'     => $time,
                    'trace'    => $trace,
                    'project'  => $project,
                    'ip'       => $ip,
                    'transfer' => $transfer,
                    'code'     => $code,
                    'details'  => str_replace(['\n', "\n"], "<br/>", $details),
                ];
            }

            $cost = round((microtime(true) - $startTIme) * 1000, 2);

            return api([
                'total' => $response['hits']['total']['value'],
                'data'  => $data,
                'cost'  => $cost
            ]);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
            return api([], 400, '搜索异常');
        }
    }

    /**
     * 使用db搜索
     *
     * @author HSK
     * @date 2022-07-14 16:30:51
     *
     * @param int $page
     * @param int $limit
     * @param string $keyword
     *
     * @return void
     */
    protected static function dbSearch($page, $limit, $keyword)
    {
        try {
            $startTIme = microtime(true);

            $where = [];
            $_source = ['day', 'time', 'trace', 'project', 'ip', 'transfer', 'code', 'details'];
            $keywordArray = explode(' ', $keyword);
            $keywordArray = array_filter($keywordArray);
            foreach ($_source as $source) {
                $where[] = [$source, "like", "%$keyword%"];
                foreach ($keywordArray as $item) {
                    $where[] = [$source, "like", "%$item%"];
                }
            }

            $list = Db::name('tracing')
                ->field('day, time, trace, project, ip, transfer, code, details')
                ->whereOr($where)
                ->order('time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page'      => $page,
                ])
                ->each(function ($item, $key) use (&$_source, &$keywordArray, &$keyword) {
                    foreach ($_source as $source) {
                        foreach ($keywordArray as $_keyword) {
                            $item[$source] = str_replace([$_keyword], "<em style='color:red;font-size: 18px !important;'>$_keyword</em>", $item[$source]);
                        }
                        $item[$source] = str_replace([$keyword], "<em style='color:red;font-size: 18px !important;'>$keyword</em>", $item[$source]);
                    }
                    return $item;
                })
                ->toArray();

            $list['cost'] = round((microtime(true) - $startTIme) * 1000, 2);

            return api($list);
        } catch (\Throwable $th) {
            \Hsk99\WebmanException\RunException::report($th);
            return api([], 400, '搜索异常');
        }
    }
}
