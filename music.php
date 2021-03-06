<?php
/**
 *
 * 音乐搜索器 - 函数声明
 *
 * @author  MaiCong <i@maicong.me>
 * @link    https://github.com/maicong/music
 * @since   1.1.3
 *
 */

if (!defined('MC_CORE') || !defined('MC_SC_CLIENT_ID')) {
    header("Location: /");
    exit();
}

// 关闭错误信息，如果要调试请注释掉
error_reporting(0);

// 引入 curl
require __DIR__.'/Curlclass/Curl.php';
use Curlclass\Curl;

// 参数处理
function stripslashes_deep($value) {
    if (is_array($value)) {
        $value = array_map('stripslashes_deep', $value);
    } elseif (is_object($value)) {
        $vars = get_object_vars($value);
        foreach ($vars as $key => $data) {
            $value->{$key}
            = stripslashes_deep($data);
        }
    } elseif (is_string($value)) {
        $value = stripslashes($value);
    }
    return $value;
}
function maicong_parse_str($string, &$array) {
    parse_str($string, $array);
    if (get_magic_quotes_gpc()) {
        $array = stripslashes_deep($array);
    }
}
function maicong_parse_args($args, $defaults = array()) {
    if (is_object($args)) {
        $r = get_object_vars($args);
    } elseif (is_array($args)) {
        $r = &$args;
    } else {
        maicong_parse_str($args, $r);
    }
    if (is_array($defaults)) {
        return array_merge($defaults, $r);
    }
    return $r;
}

// Curl 内容获取
function maicong_curl($args = array()) {
    $default = array(
        'method'     => 'GET',
        'user-agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.4 Safari/537.36',
        'url'        => null,
        'referer'    => 'https://www.google.co.uk',
        'headers'    => null,
        'body'       => null,
        'sslverify'  => false,
        'proxy'      => false,
        'range'      => false
    );
    $args         = maicong_parse_args($args, $default);
    $method       = mb_strtolower($args['method']);
    $method_allow = array('get', 'post', 'put', 'patch', 'delete', 'head', 'options');
    if (null === $args['url'] || !in_array($method, $method_allow, true)) {
        return;
    }
    $curl = new Curl();
    $curl->setOpt(CURLOPT_SSL_VERIFYPEER, $args['sslverify']);
    $curl->setUserAgent($args['user-agent']);
    $curl->setReferrer($args['referer']);
    $curl->setTimeout(20);
    $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
    if ($args['proxy'] && define('MC_PROXY') && MC_PROXY) {
        $curl->setOpt(CURLOPT_PROXY, MC_PROXY);
    }
    if (!empty($args['range'])) {
        $curl->setOpt(CURLOPT_RANGE, $args['range']);
    }
    if (!empty($args['headers'])) {
        foreach ($args['headers'] as $key => $val) {
            $curl->setHeader($key, $val);
        }
    }
    $curl->$method($args['url'], $args['body']);
    $curl->close();
    $response = $curl->raw_response;
    if (!empty($response)) {
        return $response;
    }
    return;
}

// 音频数据接口地址
function maicong_song_urls($value, $type = 'query', $site = '163') {
    if (!$value) {
        return;
    }
    $query             = ('query' === $type) ? $value : '';
    $songid            = ('songid' === $type) ? $value : '';
    $radio_search_urls = array(
        '163'        => array(
            'method'  => 'POST',
            'url'     => 'http://music.163.com/api/search/suggest/web',
            'referer' => 'http://music.163.com/',
            'proxy'   => false,
            'body'    => array(
                'csrf_token' => '',
                'limit'      => '5',
                's'          => $query
            )
        ),
        '1ting'      => array(
            'method'  => 'GET',
            'url'     => 'http://so.1ting.com/song/json',
            'referer' => 'http://m.1ting.com/',
            'proxy'   => false,
            'body'    => array(
                'page' => '1',
                'size' => '5',
                'q'    => $query
            )
        ),
        'baidu'      => array(
            'method'  => 'GET',
            'url'     => 'http://sug.music.baidu.com/info/suggestion',
            'referer' => 'http://music.baidu.com/search?key='.$query,
            'proxy'   => false,
            'body'    => array(
                'format'  => 'json',
                'version' => '2',
                'from'    => '0',
                'word'    => $query
            )
        ),
        'kugou'      => array(
            'method'     => 'GET',
            'url'        => 'http://mobilecdn.kugou.com/api/v3/search/song',
            'referer'    => 'http://m.kugou.com/v2/static/html/search.html',
            'proxy'      => false,
            'body'       => array(
                'format'   => 'json',
                'page'     => '1',
                'pagesize' => '5',
                'keyword'  => $query
            ),
            'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4'
        ),
        'kuwo'       => array(
            'method'  => 'GET',
            'url'     => 'http://search.kuwo.cn/r.s',
            'referer' => 'http://player.kuwo.cn/webmusic/play',
            'proxy'   => false,
            'body'    => array(
                'ft'       => 'music',
                'itemset'  => 'web_2013',
                'pn'       => '0',
                'rn'       => '5',
                'rformat'  => 'json',
                'encoding' => 'utf8',
                'all'      => $query
            )
        ),
        'qq'         => array(
            'method'     => 'GET',
            'url'        => 'http://open.music.qq.com/fcgi-bin/fcg_weixin_music_search.fcg',
            'referer'    => 'http://m.y.qq.com/',
            'proxy'      => false,
            'body'       => array(
                'curpage' => '1',
                'perpage' => '5',
                'w'       => $query
            ),
            'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4'
        ),
        'xiami'      => array(
            'method'     => 'GET',
            'url'        => 'http://api.xiami.com/web',
            'referer'    => 'http://m.xiami.com/',
            'proxy'      => false,
            'body'       => array(
                'v'       => '2.0',
                'app_key' => '1',
                'r'       => 'search/songs',
                'page'    => '1',
                'limit'   => '5',
                'key'     => $query
            ),
            'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4'
        ),
        '5sing'      => array(
            'method'  => 'GET',
            'url'     => 'http://search.5sing.kugou.com/home/QuickSearch',
            'referer' => 'http://5sing.kugou.com/',
            'proxy'   => false,
            'body'    => array(
                'keyword' => $query
            )
        ),
        'migu'       => array(
            'method'  => 'GET',
            'url'     => 'http://m.music.migu.cn/music-h5/search/searchAll.json',
            'referer' => 'http://m.music.migu.cn/search',
            'proxy'   => false,
            'body'    => array(
                'keyWord'  => $query,
                'type'     => 'song',
                'pageNo'   => '1',
                'pageSize' => '5'
            )
        ),
        'soundcloud' => array(
            'method'  => 'GET',
            'url'     => 'https://api-v2.soundcloud.com/search/tracks',
            'referer' => 'https://soundcloud.com/',
            'proxy'   => false,
            'body'    => array(
                'q'         => $query,
                'limit'     => '5',
                'offset'    => '0',
                'facet'     => 'genre',
                'client_id' => MC_SC_CLIENT_ID,
            )
        )
    );
    $radio_song_urls = array(
        '163'        => array(
            'method'  => 'GET',
            'url'     => 'http://music.163.com/api/song/detail/',
            'referer' => 'http://music.163.com/#/song?id='.$songid,
            'proxy'   => false,
            'body'    => array(
                'id'  => $songid,
                'ids' => '['.$songid.']'
            )
        ),
        '1ting'      => array(
            'method'  => 'GET',
            'url'     => 'http://m.1ting.com/touch/api/song',
            'referer' => 'http://m.1ting.com/#/song/'.$songid,
            'proxy'   => false,
            'body'    => array(
                'ids' => $songid
            )
        ),
        'baidu'      => array(
            'method'  => 'GET',
            'url'     => 'http://tingapi.ting.baidu.com/v2/restserver/ting',
            'referer' => 'music.baidu.com/song/'.$songid,
            'proxy'   => false,
            'body'    => array(
                'method' => 'baidu.ting.song.play',
                'format' => 'json',
                'songid' => $songid
            )
        ),
        'kugou'      => array(
            'method'     => 'GET',
            'url'        => 'http://m.kugou.com/app/i/getSongInfo.php',
            'referer'    => 'http://m.kugou.com/play/info/'.$songid,
            'proxy'      => false,
            'body'       => array(
                'cmd'  => 'playInfo',
                'hash' => $songid
            ),
            'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4'
        ),
        'kuwo'       => array(
            'method'  => 'GET',
            'url'     => 'http://player.kuwo.cn/webmusic/st/getNewMuiseByRid',
            'referer' => 'http://player.kuwo.cn/webmusic/play',
            'proxy'   => false,
            'body'    => array(
                'rid' => 'MUSIC_'.$songid
            )
        ),
        'qq'         => array(
            'method'     => 'GET',
            'url'        => 'http://i.y.qq.com/s.plcloud/fcgi-bin/fcg_list_songinfo_cp.fcg',
            'referer'    => 'http://data.music.qq.com/playsong.html?songmid='.$songid,
            'proxy'      => false,
            'body'       => array(
                'url'     => '1',
                'midlist' => $songid
            ),
            'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4'
        ),
        'xiami'      => array(
            'method'     => 'GET',
            'url'        => 'http://www.xiami.com/song/playlist/id/'.$songid.'/object_name/default/object_id/0/cat/json',
            'referer'    => 'http://m.xiami.com/song/'.$songid,
            'proxy'      => false,
            'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4'
        ),
        '5sing'      => array(
            'method'  => 'GET',
            'url'     => 'http://5sing.kugou.com/'.$songid.'.html',
            'referer' => 'http://5sing.kugou.com/'.$songid.'.html',
            'range'   => '0-2000',
            'proxy'   => false
        ),
        'migu'       => array(
            'method'  => 'GET',
            'url'     => 'http://music.migu.cn/webfront/player/findsong.do',
            'referer' => 'http://music.migu.cn/#/song/'.$songid,
            'proxy'   => false,
            'body'    => array(
                'itemid' => $songid,
                'type'   => 'song'
            )
        ),
        'soundcloud' => array(
            'method'  => 'GET',
            'url'     => 'https://api.soundcloud.com/tracks/'.$songid.'.json',
            'referer' => 'https://soundcloud.com/',
            'proxy'   => false,
            'body'    => array(
                'client_id' => MC_SC_CLIENT_ID
            )
        )
    );
    if ('query' === $type) {
        return $radio_search_urls[$site];
    }
    if ('songid' === $type) {
        return $radio_song_urls[$site];
    }
    return;
}

// 获取音频信息 - 关键词搜索
function maicong_get_song_by_name($query, $site = '163') {
    if (!$query) {
        return;
    }
    $radio_search_url = maicong_song_urls($query, 'query', $site);
    if (empty($query) || empty($radio_search_url)) {
        return;
    }
    $radio_result = maicong_curl($radio_search_url);
    if (empty($radio_result)) {
        return;
    }
    $radio_songid = array();
    switch ($site) {
        case '1ting':
            $radio_data = json_decode($radio_result, true);
            foreach ($radio_data['results'] as $key => $val) {
                $radio_songid[] = $val['song_id'];
            }
            break;
        case 'baidu':
            $radio_data = json_decode($radio_result, true);
            foreach ($radio_data['data']['song'] as $key => $val) {
                if ($key > 4) {
                    break;
                }
                $radio_songid[] = $val['songid'];
            }
            break;
        case 'kugou':
            $radio_data = json_decode($radio_result, true);
            foreach ($radio_data['data']['info'] as $key => $val) {
                $radio_songid[] = $val['hash'];
            }
            break;
        case 'kuwo':
            $radio_result = str_replace('\'', '"', $radio_result);
            $radio_data   = json_decode($radio_result, true);
            foreach ($radio_data['abslist'] as $key => $val) {
                $radio_songid[] = str_replace('MUSIC_', '', $val['MUSICRID']);
            }
            break;
        case 'qq':
            $radio_data = json_decode($radio_result, true);
            foreach ($radio_data['list'] as $key => $val) {
                $radio_hash     = explode('|', $val['f']);
                $radio_songid[] = $radio_hash[20];
            }
            break;
        case 'xiami':
            $radio_data = json_decode($radio_result, true);
            foreach ($radio_data['data']['songs'] as $key => $val) {
                $radio_songid[] = $val['song_id'];
            }
            break;
        case '5sing':
            $radio_data = json_decode(substr($radio_result, 1, -1), true);
            foreach ($radio_data['songs'] as $key => $val) {
                if ($key > 4) {
                    break;
                }
                $radio_songid[] = $val['type'].'/'.$val['songId'];
            }
            break;
        case 'migu':
            $radio_data = json_decode($radio_result, true);
            foreach ($radio_data['data']['list'] as $key => $val) {
                $radio_songid[] = $val['songId'];
            }
            break;
        case 'soundcloud':
            $radio_data = json_decode($radio_result, true);
            foreach ($radio_data['collection'] as $key => $val) {
                $radio_songid[] = $val['id'];
            }
            break;
        case '163':
        default:
            $radio_data = json_decode($radio_result, true);
            foreach ($radio_data['result']['songs'] as $key => $val) {
                $radio_songid[] = $val['id'];
            }
            break;
    }
    return maicong_get_song_by_id($radio_songid, $site, true);
}

// 获取音频信息 - 歌曲ID
function maicong_get_song_by_id($songid, $site = '163', $multi = false) {
    if (empty($songid) || empty($site)) {
        return;
    }
    $radio_song_urls = array();
    if ($multi) {
        if (!is_array($songid)) {
            return;
        }
        foreach ($songid as $key => $val) {
            $radio_song_urls[] = maicong_song_urls($val, 'songid', $site);
        }
    } else {
        $radio_song_urls[] = maicong_song_urls($songid, 'songid', $site);
    }
    if (empty($radio_song_urls) || !array_key_exists(0, $radio_song_urls)) {
        return;
    }
    $radio_result = array();
    foreach ($radio_song_urls as $key => $val) {
        $radio_result[] = maicong_curl($val);
    }
    if (empty($radio_result) || !array_key_exists(0, $radio_result)) {
        return;
    }
    $radio_songs = array();
    switch ($site) {
        case '1ting':
            foreach ($radio_result as $key => $val) {
                $radio_data   = json_decode($val, true);
                $radio_detail = $radio_data;
                if (!empty($radio_detail)) {
                    $radio_song_id = $radio_detail[0]['song_id'];
                    $radio_songs[] = array(
                        'type'   => '1ting',
                        'link'   => 'http://www.1ting.com/player/b6/player_'.$radio_song_id.'.html',
                        'songid' => $radio_song_id,
                        'name'   => $radio_detail[0]['song_name'],
                        'author' => $radio_detail[0]['singer_name'],
                        'music'  => 'http://96.1ting.com'.str_replace('.wma', '.mp3', $radio_detail[0]['song_filepath']),
                        'pic'    => $radio_detail[0]['album_cover']
                    );
                }
            }
            break;
        case 'baidu':
            foreach ($radio_result as $key => $val) {
                $radio_data   = json_decode($val, true);
                $radio_detail = $radio_data;
                if (!empty($radio_detail) && !empty($radio_detail['songinfo'])) {
                    // 注： 百度不允许外链 ~ 自信解决吧
                    $radio_song_id = $radio_detail['songinfo']['song_id'];
                    $radio_songs[] = array(
                        'type'   => 'baidu',
                        'link'   => 'http://music.baidu.com/song/'.$radio_song_id,
                        'songid' => $radio_song_id,
                        'name'   => $radio_detail['songinfo']['title'],
                        'author' => $radio_detail['songinfo']['author'],
                        'music'  => $radio_detail['bitrate']['file_link'],
                        'pic'    => $radio_detail['songinfo']['pic_big']
                    );
                }
            }
            break;
        case 'kugou':
            foreach ($radio_result as $key => $val) {
                $radio_data   = json_decode($val, true);
                $radio_detail = $radio_data;
                if (!empty($radio_detail) && $radio_data['status']) {
                    $radio_name = explode(' - ', $radio_detail['fileName']);
                    $radio_img  = array(
                        'method' => 'GET',
                        'url'    => 'http://mobilecdn.kugou.com/new/app/i/yueku.php',
                        'proxy'  => false,
                        'body'   => array(
                            'cmd'    => '104',
                            'size'   => '100',
                            'singer' => $radio_name[0]
                        )
                    );
                    $radio_imginfo = json_decode(maicong_curl($radio_img), true);
                    if (!empty($radio_imginfo)) {
                        $radio_pic = $radio_imginfo['url'];
                    }
                    $radio_song_id = $radio_detail['hash'];
                    $radio_songs[] = array(
                        'type'   => 'kugou',
                        'link'   => 'http://m.kugou.com/play/info/'.$radio_song_id,
                        'songid' => $radio_song_id,
                        'name'   => $radio_name[1],
                        'author' => $radio_name[0],
                        'music'  => $radio_detail['url'],
                        'pic'    => $radio_pic
                    );
                }
            }
            break;
        case 'kuwo':
            foreach ($radio_result as $key => $val) {
                preg_match_all('/<([\w]+)>(.*?)<\/\\1>/i', $val, $radio_data);
                if (!empty($radio_data[1]) && !empty($radio_data[2])) {
                    $radio_detail = array();
                    foreach ($radio_data[1] as $key => $val) {
                        $radio_detail[$val] = $radio_data[2][$key];
                    }
                    $radio_song_id = $radio_detail['music_id'];
                    $radio_songs[] = array(
                        'type'   => 'kuwo',
                        'link'   => 'http://www.kuwo.cn/yinyue/'.$radio_song_id,
                        'songid' => $radio_song_id,
                        'name'   => $radio_detail['name'],
                        'author' => $radio_detail['singer'],
                        'music'  => 'http://'.$radio_detail['mp3dl'].'/resource/'.$radio_detail['mp3path'],
                        'pic'    => $radio_detail['artist_pic']
                    );
                }
            }
            break;
        case 'qq':
            foreach ($radio_result as $key => $val) {
                $radio_data   = json_decode($val, true);
                $radio_detail = $radio_data['data'];
                if (!empty($radio_detail) && $radio_data['url']) {
                    $radio_pic     = substr($radio_detail[0]['albummid'], -2, 1).'/'.substr($radio_detail[0]['albummid'], -1, 1).'/'.$radio_detail[0]['albummid'];
                    $radio_song_id = $radio_detail[0]['songmid'];
                    $radio_authors = array();
                    foreach ($radio_detail[0]['singer'] as $key => $val) {
                        $radio_authors[] = $val['name'];
                    }
                    $radio_author = implode('/', $radio_authors);
                    $radio_songs[] = array(
                        'type'   => 'qq',
                        'link'   => 'https://y.qq.com/n/yqq/song/'.$radio_song_id.'.html',
                        'songid' => $radio_song_id,
                        'name'   => $radio_detail[0]['songname'],
                        'author' => $radio_author,
                        'music'  => $radio_data['url'][$radio_detail[0]['songid']],
                        'pic'    => 'http://imgcache.qq.com/music/photo/mid_album_300/'.$radio_pic.'.jpg'
                    );
                }
            }
            break;
        case 'xiami':
            foreach ($radio_result as $key => $val) {
                $radio_data   = json_decode($val, true);
                $radio_detail = $radio_data['data']['trackList'];
                if (!empty($radio_detail)) {
                    $radio_song_id = $radio_detail[0]['song_id'];
                    $radio_songs[] = array(
                        'type'   => 'xiami',
                        'link'   => 'http://www.xiami.com/song/'.$radio_song_id,
                        'songid' => $radio_song_id,
                        'name'   => $radio_detail[0]['title'],
                        'author' => $radio_detail[0]['artist'],
                        'music'  => maicong_decode_xiami_location($radio_detail[0]['location']),
                        'pic'    => $radio_detail[0]['album_pic']
                    );
                }
            }
            break;
        case '5sing':
            foreach ($radio_result as $key => $val) {
                preg_match('/ticket"\: "(.*?)"/i', $val, $radio_match);
                if (!empty($radio_match[1])) {
                    $radio_detail = json_decode(base64_decode($radio_match[1], true), true);
                    if (!empty($radio_detail)) {
                        $radio_song_id = $radio_detail['songType'].'/'.$radio_detail['songID'];
                        $radio_author = $radio_detail['singer'];
                        $radio_pic = $radio_detail['avatar'];
                        if (empty($radio_author)) {
                            preg_match('/<title>(.*?)<\/title>/i', $val, $radio_match_author);
                            if (!empty($radio_match_author[1])) {
                                $radio_author_ep = explode(' - ', $radio_match_author[1]);
                                $radio_author = $radio_author_ep[1];
                            }
                        }
                        if (empty($radio_pic)) {
                            preg_match('/img\s+src="(.*?)"\s+width="180"/i', $val, $radio_match_pic);
                            $radio_pic = !empty($radio_match_pic[1]) ? $radio_match_pic[1] : null;
                        }
                        $radio_songs[] = array(
                            'type'   => '5sing',
                            'link'   => 'http://5sing.kugou.com/'.$radio_song_id.'.html',
                            'songid' => $radio_song_id,
                            'name'   => $radio_detail['songName'],
                            'author' => $radio_author,
                            'music'  => $radio_detail['file'],
                            'pic'    => $radio_pic
                        );
                    }
                }
            }
            break;
        case 'migu':
            foreach ($radio_result as $key => $val) {
                $radio_data = json_decode($val, true);
                $radio_detail = $radio_data['msg'];
                if (!empty($radio_detail)) {
                    $radio_song_id = $radio_detail[0]['songId'];
                    $radio_songs[] = array(
                        'type'   => 'migu',
                        'link'   => 'http://music.migu.cn/#/song/'.$radio_song_id,
                        'songid' => $radio_song_id,
                        'name'   => urldecode($radio_detail[0]['songName']),
                        'author' => $radio_detail[0]['singerName'],
                        'music'  => $radio_detail[0]['mp3'],
                        'pic'    => $radio_detail[0]['poster']
                    );
                }
            }
            break;
        case 'soundcloud':
            foreach ($radio_result as $key => $val) {
                $radio_detail = json_decode($val, true);
                if (!empty($radio_detail)) {
                    $radio_streams = array(
                        'method'  => 'GET',
                        'url'     => 'https://api.soundcloud.com/i1/tracks/'.$radio_detail['id'].'/streams',
                        'referer' => 'https://soundcloud.com/',
                        'proxy'   => false,
                        'body'    => array(
                            'client_id' => MC_SC_CLIENT_ID
                        )
                    );
                    $radio_streams_info = json_decode(maicong_curl($radio_streams), true);
                    if (!empty($radio_streams_info)) {
                        $radio_music_http    = $radio_streams_info['http_mp3_128_url'];
                        $radio_music_preview = $radio_streams_info['preview_mp3_128_url'];
                        $radio_music         = $radio_music_http ? $radio_music_http : $radio_music_preview;
                    }
                    $radio_pic_artwork = $radio_detail['artwork_url'];
                    $radio_pic_avatar  = $radio_detail['user']['avatar_url'];
                    $radio_pic         = $radio_pic_artwork ? $radio_pic_artwork : $radio_pic_avatar;
                    $radio_songs[]     = array(
                        'type'   => 'soundcloud',
                        'link'   => $radio_detail['permalink_url'],
                        'songid' => $radio_detail['id'],
                        'name'   => $radio_detail['title'],
                        'author' => $radio_detail['user']['username'],
                        'music'  => $radio_music,
                        'pic'    => $radio_pic
                    );
                }
            }
            break;
        case '163':
        default:
            foreach ($radio_result as $key => $val) {
                $radio_data   = json_decode($val, true);
                $radio_detail = $radio_data['songs'];
                if (!empty($radio_detail)) {
                    $radio_song_id = $radio_detail[0]['id'];
                    $radio_authors = array();
                    foreach ($radio_detail[0]['artists'] as $key => $val) {
                        $radio_authors[] = $val['name'];
                    }
                    $radio_author = implode('/', $radio_authors);
                    $radio_songs[] = array(
                        'type'   => '163',
                        'link'    => 'http://music.163.com/#/song?id='.$radio_song_id,
                        'songid' => $radio_song_id,
                        'name'   => $radio_detail[0]['name'],
                        'author' => $radio_author,
                        'music'  => $radio_detail[0]['mp3Url'],
                        'pic'    => $radio_detail[0]['album']['picUrl'].'?param=100x100'
                    );
                }
            }
            break;
    }
    return !empty($radio_songs) ? $radio_songs : '';
}

// 获取音频信息 - url
function maicong_get_song_by_url($url) {
    preg_match('/music\.163\.com\/(#(\/m)?|m)\/song(\?id=|\/)(\d+)/i', $url, $match_163);
    preg_match('/(www|m)\.1ting\.com\/(player\/b6\/player_|#\/song\/)(\d+)/i', $url, $match_1ting);
    preg_match('/music\.baidu\.com\/song\/(\d+)/i', $url, $match_baidu);
    preg_match('/m\.kugou\.com\/play\/info\/([a-z0-9]+)/i', $url, $match_kugou);
    preg_match('/www\.kuwo\.cn\/(yinyue|my)\/(\d+)/i', $url, $match_kuwo);
    preg_match('/(y\.qq\.com\/n\/yqq\/song\/|data\.music\.qq\.com\/playsong\.html\?songmid=)([a-zA-Z0-9]+)/i', $url, $match_qq);
    preg_match('/(www|m)\.xiami\.com\/song\/(\d+)/i', $url, $match_xiami);
    preg_match('/5sing\.kugou\.com\/(m\/detail\/|)([a-z]+)(-|\/)(\d+)/i', $url, $match_5sing);
    preg_match('/music\.migu\.cn\/#\/song\/(\d+)/i', $url, $match_migu);
    preg_match('/soundcloud\.com\/[\w\-]+\/[\w\-]+/i', $url, $match_soundcloud);
    if (!empty($match_163)) {
        $songid   = $match_163[4];
        $songtype = '163';
    } elseif (!empty($match_1ting)) {
        $songid   = $match_1ting[3];
        $songtype = '1ting';
    } elseif (!empty($match_baidu)) {
        $songid   = $match_baidu[1];
        $songtype = 'baidu';
    } elseif (!empty($match_kugou)) {
        $songid   = $match_kugou[1];
        $songtype = 'kugou';
    } elseif (!empty($match_kuwo)) {
        $songid   = $match_kuwo[2];
        $songtype = 'kuwo';
    } elseif (!empty($match_qq)) {
        $songid   = $match_qq[2];
        $songtype = 'qq';
    } elseif (!empty($match_xiami)) {
        $songid   = $match_xiami[2];
        $songtype = 'xiami';
    } elseif (!empty($match_5sing)) {
        $songid   = $match_5sing[2].'/'.$match_5sing[4];
        $songtype = '5sing';
    } elseif (!empty($match_migu)) {
        $songid   = $match_migu[1];
        $songtype = 'migu';
    } elseif (!empty($match_soundcloud)) {
        $match_resolve = array(
            'method'  => 'GET',
            'url'     => 'http://api.soundcloud.com/resolve.json',
            'referer' => 'https://soundcloud.com/',
            'proxy'   => false,
            'body'    => array(
                'url'       => $match_soundcloud[0],
                'client_id' => MC_SC_CLIENT_ID
            )
        );
        $match_request = maicong_curl($match_resolve);
        preg_match('/tracks\/(\d+)\.json/i', $match_request, $match_location);
        if (!empty($match_location)) {
            $songid   = $match_location[1];
            $songtype = 'soundcloud';
        }
    } else {
        return;
    }
    return maicong_get_song_by_id($songid, $songtype);
}

// 解密虾米 location
function maicong_decode_xiami_location($location) {
    $location = trim($location);
    $result   = array();
    $line     = intval($location[0]);
    $locLen   = strlen($location);
    $rows     = intval(($locLen - 1) / $line);
    $extra    = ($locLen - 1) % $line;
    $location = substr($location, 1);
    for ($i = 0; $i < $extra; ++$i) {
        $start    = ($rows + 1) * $i;
        $end      = ($rows + 1) * ($i + 1);
        $result[] = substr($location, $start, $end - $start);
    }
    for ($i = 0; $i < $line - $extra; ++$i) {
        $start    = ($rows + 1) * $extra + ($rows * $i);
        $end      = ($rows + 1) * $extra + ($rows * $i) + $rows;
        $result[] = substr($location, $start, $end - $start);
    }
    $url = '';
    for ($i = 0; $i < $rows + 1; ++$i) {
        for ($j = 0; $j < $line; ++$j) {
            if ($j >= count($result) || $i >= strlen($result[$j])) {
                continue;
            }
            $url .= $result[$j][$i];
        }
    }
    $url = urldecode($url);
    $url = str_replace('^', '0', $url);
    return $url;
}

// Ajax Post
function ajax_post($key) {
    return (!empty($_POST[$key]) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ? $_POST[$key] : null;
}

function server($key) {
    return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
}
