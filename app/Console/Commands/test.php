<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\TemplateRecords;
use App\Models\Epg;
use App\Models\Record;
use App\Models\Template;
use App\Tools\ChannelGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test {v?} {d?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $group = $this->argument('v') ?? "";
        $day = $this->argument('d') ?? "2024-02-06";
        
        $data = $this->getRawData();

        foreach($data as $line)
        {
            $words = explode("\t", $line);
            $p = Record::where('name', $words[0])->first();
            if($p) {
                if($p->name2 == $words[2]) continue;
                $p->name2 = $words[2];
                $cates = explode(' ', str_replace('/',' ', $words[1]));
                $categorys = $p->category;
                foreach($cates as $c)
                {
                    $cc = $this->parseTag($c);
                    if($cc)$categorys[] = $cc;
                    
                }
                $p->category = $categorys;
                $p->save();

                //print_r($p->toArray());
                //break;
            }
            
        }
        exit;

        $day = strtotime($day);

        $channel = new Channel();
            $channel->id = 1;
            $channel->name = $group;
            $channel->air_date = date('Y-m-d', $day);

        $templates = Template::with('records')->where(['group_id'=>$group,'schedule'=>Template::DAILY,'status'=>Template::STATUS_SYNCING])->orderBy('sort', 'asc')->get();
        
        foreach($templates as $template)
        {
            $template_items = $template->records;

            $template_item = $this->findAvailableTemplateItem($channel, $template_items);

            print_r($template_item->toArray());
        }

        return 0 ;

        for($i=0;$i<20;$i++)
        {
            $channel = new Channel();
            $channel->id = $i;
            $channel->name = $group;
            $channel->air_date = date('Y-m-d', $day);
            $day += 86400;

            $this->warn("start date:" . $channel->air_date);
            $air = 0;
            $duration = 0;
            $epglist = [];
            
            foreach($templates as $template)
            {
                if($air == 0) $air = strtotime($channel->air_date.' '.$template->start_at);  
                $epglist = []; 
                // This is one single Program
                $program = ChannelGenerator::createChannelProgram($template);

                $program->channel_id = $channel->id;
                $program->start_at = date('Y-m-d H:i:s', $air);

                $template_items = $template->records;

                $template_item = $this->findAvailableTemplateItem($channel, $template_items);

                if(!$template_item) {
                    $this->info("没有找到匹配的模版数据: {$template->id} {$template->category}");
                    continue;
                }

                $this->info("template data: ".$template_item->data['episodes'].', '.$template_item->data['unique_no'].', '.$template_item->data['result'] );

                $maxDuration = ChannelGenerator::parseDuration($template->duration); + (int)config('MAX_DURATION_GAP', 600);
                $items = $this->findAvailableRecords($template_item, $maxDuration);

                if(count($items)) {
                    foreach($items as $item) {
                        $seconds = ChannelGenerator::parseDuration($item->duration);
                        if($seconds > 0) {
                            
                            $duration += $seconds;
                            
                            $line = ChannelGenerator::createItem($item, $template_item->category, date('H:i:s', $air));
                            
                            $air += $seconds;

                            $line['end_at'] = date('H:i:s', $air);

                            $epglist[] = $line;
                                
                            //$this->info("添加节目: {$template_item->category} {$item->name} {$item->duration}");



                        }
                        else {

                            $this->warn(" {$item->name} 的时长为 0 （{$item->duration}）, 因此忽略.");
                            //throw new GenerationException("{$item->name} 的时长为 0 （{$item->duration}）", Notification::TYPE_GENERATE);
                        }
                    }
                    if(count($epglist) == 0) {
                        $this->error(" 异常1，没有匹配到任何节目  {$template_item->id} {$template_item->category}");
                    }
                }
                else {
                    $this->error(" 异常2，没有匹配到任何节目  {$template_item->id} {$template_item->category}");
                }

                $program->duration = $duration;
                $program->data = json_encode($epglist);
                $program->end_at = date('Y-m-d H:i:s', $air);
            }
        }

        return 0;
    }

    private function findAvailableRecords(TemplateRecords &$template, $maxDuration)
    {
        $items = [];
        if($template->type == TemplateRecords::TYPE_RANDOM) {
            $temps = Record::findNextAvaiable($template, $maxDuration);
            if(in_array($temps[0], ['finished', 'empty'])) {
                $d = $template->data;
                $d['episodes'] = null;
                $d['unique_no'] = '';
                $d['name'] = '';
                $d['result'] = '';
                $template->data = $d;

                $temps = Record::findNextAvaiable($template, $maxDuration);
            }
            $d = $template->data;
            foreach($temps as $item) {
                if(!in_array($item, ['finished', 'empty'])) {
                    $items[] = $item;
                    $d['episodes'] = $item->episodes;
                    $d['unique_no'] = $item->unique_no;
                    $d['name'] = $item->name;
                    $d['result'] = '编排中';
                    $template->data = $d;
                }
            }
            
        }
        else if($template->type == TemplateRecords::TYPE_STATIC) {
                
            $temps = Record::findNextAvaiable($template, $maxDuration);
            $items = [];

            if(in_array($temps[0], ['finished', 'empty'])) return $items;
            
            $d = $template->data;
            foreach($temps as $item) {
                if($item == 'empty') {
                    $d['result'] = '未找到';
                }
                else if($item == 'finished') {
                    $d['result'] = '编排完';
                }
                else {
                    $items[] = $item;
                    $d['episodes'] = $item->episodes;
                    $d['unique_no'] = $item->unique_no;
                    $d['name'] = $item->name;
                    $d['result'] = '编排中';
                }
                $template->data = $d;
                //$p->save();
            }
        }

        return $items;
    }

    private function findAvailableTemplateItem($channel, $templateItems)
    {
        $air = strtotime($channel->air_date);
        $dayofweek = date('N', $air);

        $this->info("dayofweek: ".$dayofweek);

        foreach($templateItems as &$p)
        {
            if(!in_array($dayofweek, $p->data['dayofweek'])) continue;
            $begin = $p->data['date_from'] ? strtotime($p->data['date_from']) : 0;
            $end = $p->data['date_to'] ? strtotime($p->data['date_to']) : 999999999999;
            if($air < $begin || $air > $end) {
                $lasterror = "{$p->id} {$p->category} 编排设定时间 {$p->data['date_from']}/{$p->data['date_to']} 已过期";
                continue;
            }

            if($p->data['result'] == '编排完') continue;

            return $p;
        }

        return false;
    }


    private function getRawData()
    {
        $str = <<<EOF
        物料库 节目库现已导入的482部电影的【标题】	GENRE	ENGLISH TITLE	CHINESE TITLE	Duration
2分之1段情	劇情	Infatuation	1/2段情(N.G.慢半拍)	88min
A计划	动作	Project A	A計劃	99min
A计划续集	动作	Project A Part II	A計劃續集	102min
BB30	劇情	BB30	BB30	86min
beyond日记之莫欺少年穷	劇情	Beyond's Diary	BEYOND日記之莫欺少年窮	90min
office有鬼	恐怖	Haunted Office	Office 有鬼	89min
YES一族	劇情	Fruit Punch	YES一族(菜鳥大亨)	92min
阿飞正传	劇情	Fate	阿飛正傳	80min
阿郎的故事	劇情	All About Ah Long	阿郎的故事(AKA:又見阿郎)	95min
阿修罗	动作	Saga Of The Phoenix	阿修羅(AKA:阿修羅傳奇)	89min
爱断了线	爱情	Sky Of Love	愛,斷了線	90min
爱情谜语	劇情	Chaos By Design	愛情謎語(AKA:愛情像什麼)	83min
爱上我吧	爱情	Gimme, Gimme	愛上我吧	101min
爱神1号	劇情	Cupid One	愛神一號	92min
安乐战场	喜劇	Fatal Vacation	安樂戰場	87min
暗夜	劇情	Dark Night	暗夜	89min
暗战	動作 劇情	Running Out Of Time	暗戰	93min
暗战2	動作 劇情	Running Out Of Time 2	暗戰2 	96min
八喜临门	喜劇	My Family	八喜臨門	86min
八星报喜	喜劇	Eighth Happiness	八星報喜	88min
霸王花	动作	Inspector Wears Skirts, The	霸王花	91min
霸王卸甲	动作	Bury Me High	霸王卸甲	95min
百变星君	喜劇	Sixty Million Dollar Man	百變星君 	91min
百年好合	爱情	Love For All Seasons	百年好合	92min
败家子	动作	Prodigal Son, The	敗家子(港:敗家仔)	95min
绑错票	劇情	To Err Is Humane	綁錯票(港:標錯參)	85min
笨小孩	劇情	Crying Heart	笨小孩	104min
碧血蓝天	動作 劇情	Blacksheep Affair, The	碧血藍天	92min
边缘岁月	劇情	Killer's Blue, A	邊緣歲月(AKA:風雲歲月)	86min
表哥到	靈幻	My Cousin The Ghost	表哥到	91min
不是冤家不聚头	劇情	Wrong Couples,The	不是冤家不聚頭	88min
不脱袜的人	劇情	Fishy Story, A	不脫襪的人	94min
不准掉头	喜劇	No U-Turn	不准掉頭	88min
操行零分	劇情	Conduct Zero	操行零分	83min
长短脚之恋	劇情	Fractured Follies	長短腳之戀	91min
超级女警	动作	Super Lady Cop *	超級女警(AKA:狂鳳密令)	86min
超级市民	喜劇	Super Citizen	超級市民	89min
城市猎人	喜劇	City Hunter *	城市獵人	95min
城市特警	动作	Big Heat, The	城市特警(AKA:大行動)	91min
痴心的我	劇情	Devoted To You	痴心的我	87min
赤胆情	动作	No Compromise	赤膽情	90min
冲激21	劇情	Energetic 21	衝激 21	92min
冲破黑漩涡	劇情	Breakthough the Black Wheel 	衝破黑漩渦	89min
川岛芳子	劇情	Kawashima Yoshiko	川島芳子	105min
穿牛仔裤的钟馗	靈幻	The Blue Jean Monster(Monster Wore Jeans)	穿牛仔褲的鍾馗(港:著牛仔褲的鍾馗)	88min
错在新宿	劇情	Brief Encounter In Shinjuku	錯在新宿(AKA:小男人週記續集)	95min
搭错车	劇情	Papa Can You Hear Me Sing	搭錯車	87min
打工皇帝	喜劇	Working Class	打工皇帝	95min
大话神探	喜劇	Fumbling Cops	大話神探	93min
大冒险家	動作 劇情	Adventurers, The	大冒險家	105min
大闹广昌隆	劇情	Finale In Blood	大鬧廣昌隆(牽魂-台譯)	88min
大内密探零零发	動作 喜劇	Forbidden City Cop	大內密探零零發 	89min
大丈夫日记	喜劇	Diary Of A Big Man	大丈夫日記	86min
大只佬	劇情	Running On Karma	大隻佬	93min
大追击	劇情	Can't Stop The War	大追擊	100min
呆佬拜寿	喜劇	Only Fools Fall In Love	呆佬拜壽	107min
代客泊车	劇情	Parking Service	代客泊車(AKA:代客停車)	87min
带剑的小孩	劇情	Kidnapped	帶劍的小孩	91min
单身贵族	劇情	Nobles, The	單身貴族	92min
刀马旦	动作	Peking Opera Blue	刀馬旦	101min
等待黎明	劇情	Hong Kong 1941	等待黎明	95min
等候董建华发落	劇情	From The Queen To The Chief Executive	等候董建華發落	106min
低一点的天空	劇情	Happy Go Lucky	低一點的天空	95min
地下情	劇情	Love Unto Wastes	地下情	91min
第一诫	动作/惊悚  	Rule #1	第一诫	95min
颠佬正传	劇情	Lunatics, The	癲佬正傳	88min
喋血街头	动作	Bullet in The Head	喋血街頭	104min
喋血双雄	动作	Killer, The	喋血雙雄	96min
东方秃鹰	喜劇	Eastern Condors	東方禿鷹	93min
赌霸	劇情 赌博	Queen Of Gamblers(Top Bet, The )	賭霸	88min
赌侠1999	喜剧/赌博	Conman, The	賭俠 1999	107min
赌侠大战拉斯维加斯	喜剧/赌博	Conmen In Vegas, The	賭俠大戰拉斯維加斯	97min
对不起多谢你	劇情	My Dad Is A Jerk	對不起，多謝你	107min
多情种	喜劇	My Little Sentimental Friend	多情種	92min
夺宝计上计	动作	From Here To Prosperity	奪寶計上計	86min
夺命佳人	劇情	Lady In Black	奪命佳人	89min
恩怨情天	劇情/赌博	Killer's Nocturne	恩怨情天 (港:不夜天)	84min
发达先生	喜劇	Mr Fortune	發達先生	91min
废柴同盟	喜劇	Losers' Club, The	廢柴同盟	89min
返老还童	喜劇	Forever Young	返老還童	91min
妃子笑	喜劇	The China's Next Top Princess	妃子笑	97min
飞虎队	动作	Flying Tigers	飛虎隊	102min
飞虎奇兵	劇情	City Hero	飛虎奇兵	93min
飞龙猛将	动作	Dragons Forever	飛龍猛將	90min
飞鹰计划	动作	Armour Of God II	飛鷹計劃	103min
飞跃羚羊	劇情	United We Stand	飛躍羚羊	88min
风流三壮士	喜劇	Three Lustketeers, The	風流三壯士	92min
风雨双流星	动作	Killer Meteors, The	風雨雙流星	100min
风雨同路	劇情	Unmatchable Match, The	風雨同路	93min
福星高照	喜劇	My Lucky Stars	福星高照	92min
复仇者	动作	Sweet Vengence	復仇者	85min
富贵逼人	喜劇	It's A Mad Mad World	富貴逼人	91min
富贵吉祥	喜劇	Perfect Match	富貴吉祥	86min
富贵列车	喜劇	Millionaires' Express	富貴列車	86min
富贵再逼人	喜劇	It's A Mad Mad World II	富貴再逼人	86min
富贵再三逼人	喜劇	It's A Mad Mad World III	富貴再三逼人	90min
呷醋大丈夫	喜劇	Goodbye Darling	呷醋大丈夫	92min
肝胆相照	动作	Sworn Brothers	肝膽相照	85min
歌舞升平	劇情	Musical Singer	歌舞昇平(aka:小子高飛)	89min
给爸爸的信	動作 劇情	My Father Is A Hero	給爸爸的信	104min
公子多情	劇情	Greatest Lover, The	公子多情	98min
恭喜发财	喜劇	Kung Hei Fat Choy	恭喜發財 (神仙龍虎豹-台譯)	86min
孤恋花	劇情	Love Lone Flower	孤戀花	86min
孤男寡女	爱情	Needing You	孤男寡女	104min
古惑大律师	劇情	Queen's Bench III	古惑大律師(搞怪大律師-台譯)	90min
鬼打鬼	恐怖	SPOOKY ENCOUNTERS(Encounter of The Spooky Kind)	鬼打鬼	99min
鬼媾人	靈幻	Ghost Fever	鬼媾人	91min
鬼计	恐怖	Dead Air	鬼計	86min
鬼马保护贼美人	喜劇	Good The Bad & The Beauty, The	鬼馬保鑣賊美人	95min
鬼马狂想曲	喜劇	Fantasia	鬼馬狂想曲	100min
鬼马校园	喜劇	Porky's Meatball	鬼馬校園	92min
鬼马智多星	喜劇	All The Wrong Clues	鬼馬智多星(AKA:夜來香)	94min
鬼马朱唇	劇情	Merry Go Round	鬼馬朱唇(AKA: 打帶跑)	88min
鬼线人	恐怖	Ghost Informer, The	鬼綫人(線)	81min
鬼新娘	靈幻	Spiritual Love	鬼新娘	85min
帼四英雄传	动作	Loser, The Hero, The	幗四英雄傳	91min
过埠新娘	劇情	Paper Marriage	過埠新娘(AKA:小白兔與大烏龜)	88min
海狼	动作	Sea Wolves	海狼	87min
海上花	劇情	Immortal Story	海上花	93min
害时出世	靈幻	Red Panther, The	害時出世(AKA:風流神探殺人狂)	81min
何必有我	劇情	Why Me	何必有我	86min
何方神圣	喜劇	Crazy Chase, The	何方神聖	98min
何日君再来	劇情	Till We Meet Again(Au Revoir Mon Amour)	何日君再來	99min
黑金	劇情	Island Of Greed	黑金 	120min
黑马王子	爱情	Prince Charming	黑馬王子	95min
黑猫	动作	Black Cat	黑貓	88min
黑猫2	动作	Black Cat II	黑貓 II	87min
黑侠	动作	Black Mask	黑俠 	100min
黑心鬼	恐怖 喜劇	Three (3) Wishes	黑心鬼	95min
黑雪	劇情	Will Of Iron	黑雪	81min
横财三千万	喜劇	Thirty Million Rush, The	橫財三千萬	94min
鸿运当头	喜劇	Life Line Express	鴻運當頭	83min
花心梦里人	喜劇	Dream Of Desire	花心夢裡人	92min
花心三剑侠	劇情	Unfaithfully Yours	花心三劍俠	85min
花仔多情	劇情	Affectionately Yours	花仔多情(AKA:嫁錯老婆)	89min
滑稽时代	喜劇	Laughing Time	滑稽時代(aka:滑稽世界)	93min
画中仙	靈幻	Picture Of A Nymph	畫中仙	93min
坏女孩	劇情	Why Why Tell Me Why	壞女孩 	89min
欢乐叮当	喜劇	Happy Ding Dong(Happy Din Don)	歡樂叮噹	91min
欢乐龙虎榜	喜劇	Book Of Heroes, A	歡樂龍虎榜	87min
欢乐神仙窝	喜劇	Beware Of Pickpockets	歡樂神仙窩(AKA:警察鬥小偷)	88min
欢乐五人组	劇情	Goofy Gang, The	歡樂五人組	90min
皇家饭	劇情	Law Enforcer, The	皇家飯(AKA:公家飯)	92min
皇家女将	喜劇	She Shoots Straight	皇家女將	86min
皇家师姐3之雌雄大盗	动作	In The Line Of Duty III	皇家师姐3之雌雄大盜	79min
皇家师姐直击证人	动作	In The Line Of Duty 4	皇家师姐直擊証人	91min
皇家师姐	动作	Yes, Madam!	皇家師姐	89min
皇家战士	动作	Royal Warriors	皇家戰士	92min
黄飞鸿	动作	Once Upon A Time In China	黃飛鴻	128min
黄飞鸿92之龙行天下	动作	Masters, The	黃飛鴻'92之龍行天下(龍行天下)	85min
黄飞鸿之男儿当自强	动作	Once Upon A Time In China II	黃飛鴻之二男兒當自強(AKA: 男兒當自強)	106min
黄飞鸿之西域雄狮	动作	Once Upon A Time In China And America	黃飛鴻之西域雄獅	99min
火爆浪子	动作	Angry Ranger	火爆浪子	85min
火烛鬼	劇情	Burning Sensation	火燭鬼(AKA:表哥到續集)	87min
吉人天相	喜劇	Chase A Fortune	吉人天相	87min
吉星拱照	喜劇	Fun The Luck & The Tycoon, The	吉星拱照(AKA:吉星高照)	87min
急冻奇侠	动作	Iceman Cometh	急凍奇俠	108min
极道追踪	劇情	Zodiac Killers (Zodiac Hunters) 	極道追踪	85min
极速传说	動作 劇情	Legend Of Speed, The	極速傳說	109min
继续跳舞	劇情	Keep On Dancing	繼續跳舞	86min
佳人有约	喜劇	The Perfect Match	佳人有約	95min
家在香港	劇情	Home At Hong Kong, The	家在香港	93min
夹心沙展	动作	Fingers on Triggers	夾心沙展(夾心警曹)(槍手警察-台譯)	89min
奸人本色	喜劇	Who Is The Craftiest	奸人本色	83min
煎醸三宝	喜劇	Three Of A Kind	煎釀叁寶	101min
监狱不设防	劇情	Jail House Eros	監獄不設防	84min
监狱风云2大逃犯	动作	Prison On Fire II	監獄風雲II之逃犯(AKA: 監獄風雲 II大逃犯)	105min
咸鱼翻生	喜劇	By Hook Or By Crook	鹹魚翻生(AKA:狗急跳牆)	87min
僵尸叔叔	恐怖	Mr. Vampire Saga 4	殭屍叔叔	89min
僵尸医生	恐怖	Doctor Vampire	殭屍醫生	92min
锦绣前程	劇情	Long And Winding Road	錦繡前程	104min
精武门	动作	Fist Of Fury *	精武門	101min
惊魂记	劇情	Web Of Deception	驚魂記	89min
警察故事	动作	Police Story	警察故事	96min
警察故事3之超级警察	动作	Police Story III-Super Cop	警察故事 III 之超級警察	91min
警察故事续集	动作	Police Story Part II	警察故事續集	101min
警贼兄弟	劇情	Caper	警賊兄弟	80min
救命宣言	劇情	Doctor's Heart	救命宣言	85min
绝世好bra	喜劇	La Brassiere	絕世好Bra	110min
绝世好宾	喜劇	Driving Miss Wealthy	絕世好賓	102min
君子好逑	喜劇	Other Side Of Gentleman, The	君子好逑	89min
开心鬼	喜劇	Happy Ghost, The	開心鬼	93min
开心鬼5上错身	喜劇	Happy Ghost V	開心鬼5上錯身	88min
开心鬼放暑假	喜劇	Happy Ghost II	開心鬼放暑假	96min
开心鬼撞鬼	喜劇	Happy Ghost III	開心鬼撞鬼	84min
开心快活人	喜劇	Happy Go Lucky	開心快活人	90min
开心三响炮	喜劇	Funny Triple	開心三嚮炮(AKA:快速連環炮)	86min
开心勿语	喜劇	Trouble Couples	開心勿語	85min
空心大少爷	喜劇	Just For Fun	空心大少爺	97min
孔雀王子	动作	Peacock King	孔雀王子(AKA:孔雀王)	80min
恐怖鸡	恐怖	Intruder	恐怖雞	87min
快餐车	动作	Wheels On Meals	快餐車	98min
辣手回春	劇情	Help!!!	辣手回春	89min
蓝色霹雳火	动作	Blue Lightning	藍色霹靂火(AKA:藍色時分)	87min
烂赌英雄	喜劇/赌博	Born To Gamble	爛賭英雄(AKA:好賭英雄)	94min
老夫子2001	喜劇	Master Q 2001	老夫子2001	103min
老虎出更	喜劇	Tiger On Beat	老虎出更(老虎出差)	88min
老虎出监	动作	Run, Don't Walk	老虎出監	84min
老猫	劇情	The Cat(1000 Years Cat, The)	老貓	83min
老鼠街	动作	Gold Hunter	老鼠街	84min
雷霆扫穴	动作	Red Shield	雷霆掃穴	83min
冷面狙击手	动作	Tiger Cage III	冷面狙擊手	89min
呖咕呖咕新年财	喜剧/赌博	Fat Choi Spirit	嚦咕嚦咕新年財 	97min
恋爱季节	劇情	Kiss Me Goodbye	戀愛季節	85min
恋情告急	爱情	Love On The Rocks	戀情告急	103min
恋上你的床	爱情	Good Times, Bed Times	戀上你的床	104min
恋性世代	爱情	I Do	戀性世代	87min
恋战冲绳	爱情	Okinawa Rendez-vous	戀戰沖繩	99min
良宵花弄月	劇情	That Enchanting Night	良宵花弄月	87min
两个只能活一个	動作 劇情	Odd One Dies, The	兩個只能活一個	90min
两公婆八条心	劇情	Strange Bedfellow	兩公婆八條心(變形人-台譯)	83min
两只老虎	劇情	Run Tiger Run	兩隻老虎	87min
烈火战车	劇情	Full Throttle	烈火戰車	108min
烈血风云	动作	Bloody Fight, A	烈血風雲(AKA:殺手無情)	88min
凌晨晚餐	靈幻	Vampire's Breakfast	凌晨晚餐	81min
灵幻先生	靈幻	Mr. Vampire Part III	靈幻先生(AKA:靈幻道士)	88min
灵气迫人	喜劇	Occupant, The	靈氣迫人	91min
流氓暴发户	喜劇	Rich Man	流氓暴发户(AKA:何日金再來)	72min
流氓公仆	动作	Cop Of The Town	流氓公僕 (公僕與公敵-台譯)	89min
流氓英雄	动作	Innocent Interloper, The	流氓英雄(流氓秀才-台譯)	94min
龙城正月	劇情	Dragon Town Story	龍城正月	93min
龙的传人	喜劇	Legend Of The Dragon	龍的傳人	92min
龙的心	劇情	Heart Of Dragon	龍的心	87min
龙凤智多星	喜劇	Intellectual Trio,The	龍鳳智多星	84min
龙虎风云	动作	City On Fire	龍虎風雲	101min
龙虎家族	动作	Fiery Family, A	龍虎家族(AKA:重出江湖)	92min
龙少爷	动作	Dragon Lord	龍少爺	84min
龙腾虎跃	动作	Fearless Hyena II	龍騰虎躍	88min
龙兄虎弟	动作	Armour Of God	龍兄虎弟	94min
龙在江湖	動作 劇情	True Mob Story, A	龍在江湖	112min
乱世儿女	喜劇	Shanghai Shanghai	亂世兒女	83min
买起曼克顿	劇情	Taking Manhattan	買起曼克頓(獵煞-台譯)	82min
猫头鹰	喜劇	Legend Of The Owl, The	貓頭鷹 (糊塗三少爺-台譯)	88min
猫头鹰与小飞象	动作	Owl Vs Bumbo, The	貓頭鷹與小飛象	94min
冒牌大贼	喜劇	Who's The Crook	冒牌大賊	91min
冒险王	动作	Dr. Wai In "The Scripture With No Words"	冒險王	90min
每天吓你8小时	恐怖	Ghost Office	每天嚇你八小時	89min
梦中人	劇情	Dream Lovers	夢中人	88min
妙计连环套	动作	Winners Takes All	妙計連環套(港譯:有Friend冇驚)	88min
摩登保镖	喜劇	Security Unlimited	摩登保鏢	87min
摩登神探	喜劇	Modern Detective	摩登神探	84min
摩登天师	靈幻	To Hell With Devil	摩登天師	87min
摩登衙门	喜劇	Oh My Cops	摩登衙門	93min
魔高一丈	劇情	Return Of The Demon	魔高一丈	92min
魔画情	靈幻	Fantasy Romance	魔畫情(AKA:隔世情)	83min
母牛一条	劇情	Scalper, The (Breadline Blues)	母牛一條	107min
难得有情郎	劇情	Lover at Large	難得有情郎(AKA:引郎入世)	85min
难兄难弟	劇情	It Takes Two	難兄難弟	92min
你ok我ok	劇情	You're OK, I'm OK	你OK, 我OK	82min
扭计杂牌军	动作	Naughty Boys(Violent Caper)	扭計雜牌軍(槓上開-台譯)	90min
女机械人	劇情	Robotrix	女機械人	88min
女人风情话	劇情	Hong Kong Grafitti	女人風情話	88min
潘金莲之前世今生	劇情	Reincarnation Of Golden Lotus	潘金蓮之前世今生	84min
喷火女郎	劇情	She Starts The Fire	噴火女郎           	84min
朋党	劇情	Against All	朋黨	90min
霹雳大喇叭	动作	Where's Officer Tuba	霹靂大喇叭	89min
霹雳先锋	动作	Final Justice	霹靂先鋒	93min
霹雳战士	动作	Future Hero, The	霹靂戰士	91min
七年很痒	喜劇	Itchy Heart	七年很癢	90min
七年之痒	喜劇	Seven Years Itch	七年之癢	82min
奇迹	动作	Mr. Canton And Lady Rose (Miracles)	奇蹟	130min
奇门遁甲	靈幻	Miracle Fighters, The	奇門遁甲	89min
奇谋妙计五福星	喜劇	Winners And Sinners	奇謀妙計五福星(五福星-台譯)	104min
奇缘	劇情	Witch From Nepal	奇緣	84min
汽水加牛奶	劇情	Cream Soda & Milk	汽水加牛奶(忌濂溝鮮奶-港譯)	89min
千王	動作/赌博	Great Pretenders	千王	91min
钱作怪	喜劇	From Riches To Rags	錢作怪	102min
倩女幽魂	靈幻	Chinese Ghost Story, A	倩女幽魂	91min
倩女幽魂2人间道	靈幻	Chinese Ghost Story II, A	倩女幽魂 II 人間道	98min
倩女幽魂3道道道	靈幻	Chinese Ghost Story III, A	倩女幽魂 III 道道道	104min
抢闸孖孖星	劇情	Diamond Debacle,The (Coup de Grace)	搶閘孖孖星(AKA:起尾注)(海底撈)	85min
青春怒潮	劇情	GROW UP IN ANGER	青春怒潮(aka:好小伙子)	89min
青蛇杀手	动作	Snake Fist	青蛇殺手	82min
情逢敌手	喜劇	Mismatched Couples	情逢敵手	90min
情圣	喜劇	Magnificent Scoundrels, The	情聖	86min
情义心	动作	Fury	情義心	88min
情债	劇情	Rapist Beckon	情債	83min
秋天的童话	劇情	Autumn's Tale, An	秋天的童話	93min
秋天日记	爱情	Autumn Diary	秋天日記	104min
求爱夜惊魂	靈幻	In Between Loves	求愛夜驚魂	88min
求恋期	喜劇	Cause We Are Young	求戀期	91min
全家福	喜劇	Family Affair, A	全家福	92min
拳王	劇情	Boxer's Story, A	拳王	100min
群龙夺宝	动作	Three Against The World	群龍奪寶(AKA:獵犬、神鎗、老狐狸)	83min
人鬼情未了	靈幻	Alien Wife (Pretty Ghost)	人鬼情未了(港译:我老婆唔係人)	94min
人在江湖	劇情	A Mob Story	人在江湖	86min
柔道龙虎榜	動作 劇情	Throw Down	柔道龍虎榜	95min
三对鸳鸯一张床	劇情	Couple Couples Couples	三對鴛鴦一張床(枕邊宣言-台譯)	86min
三狼奇案	劇情	Sentenced To Hang	三狼奇案	99min
三人世界	劇情	Heart To Hearts	三人世界	93min
三人新世界	劇情	Heart Into Hearts	三人新世界	87min
沙滩仔与周师奶	喜劇	Royal Scoundrel	沙灘仔與周師奶	85min
杀妻二人组	劇情	100 Ways To Murder Your Wife	殺妻二人組	91min
杀手之王	動作 劇情	Hitman	殺手之王(夺命杀手天使情)	104min
杀之恋	劇情	Fatal Love	殺之戀	86min
山水有相逢	劇情	Golden Girls, The	山水有相逢	94min
上海皇帝之雄霸天下	劇情	Lord Of East China Sea II	上海皇帝之雄霸天下(AKA:歲月風雲續集)	97min
上海假期	劇情	American Grandson	上海假期	82min
上海滩大亨	劇情	Big Boss Of Shanghai	上海灘大亨	92min
上天救命	喜劇	Heaven Can Help	上天救命	85min
少林俗家弟子	动作	Disciples Of Shaolin Temple, The	少林俗家弟子	94min
少年往事	劇情	Memory of Youth	少年往事	88min
少爷发威	喜劇	Play Catch	少爺发威（AKA:小生發威）	90min
摄氏32度	劇情	Beyond Hypothermia	攝氏32度	86min
神奇两女侠	劇情	Wonder Women	神奇兩女俠	94min
神探马如龙	喜劇	Inspector Pink Dragon	神探馬如龍(AKA:痞子探長)	91min
神探朱古力	喜劇	Chocolate Inspector	神探朱古力	95min
神勇飞虎霸王花	动作	Inspector Wears Skirt II, The	神勇飛虎霸王花(AKA:霸王花續集)	91min
神勇双响炮	喜劇	Pom Pom	神勇雙嚮炮(AKA:雙嚮炮)	92min
神勇双响炮续集	喜劇	Rosa	神勇雙嚮炮續集(神勇雙嚮炮)	92min
审死官翻案	喜劇	Justice My Fool	審死官翻案	77min
生命快车	劇情	Express, The	生命快車	96min
生死决	武俠	Duel To The Death	生死決(AKA: 必勝之戰)	82min
生死线	劇情	Island, The	生死線	89min
省港骑兵	动作	Long Arm Of The Law	省港旗兵	97min
省港奇兵续集	动作	Long Arm Of The Law II	省港旗兵續集	79min
圣诞快乐	喜劇	Merry Christmas	聖誕快樂 (我愛光頭-台譯)	92min
圣诞奇遇结良缘	动作	It's A Drink! It's A Bomb!	聖誕奇遇結良緣	87min
失忆界女王	喜劇	Why me, Sweetie?!	失憶界女王	97min
师弟出马	动作	Young Master, The	師弟出馬	101min
师姐大晒	动作	The Blonde Fury(Lady Reporter)	師姐大晒(aka:師姐出馬)	84min
时来运转	靈幻	Those Merry Souls	時來運轉	93min
瘦身男女	爱情	Love On A Diet	瘦身男女	95min
蜀山	武俠	Zu Warriors From Magic Mount	蜀山(新蜀山劍俠)	94min
蜀山传	动作	Legend Of Zu, The	蜀山傳	103min
衰鬼撬墙脚	靈幻	Till Death Shall We Start	衰鬼撬牆腳(衰鬼要上床-台譯)	88min
双肥临门	喜劇	Double Fattiness	雙肥臨門	92min
双龙出海	动作	Return Of Pom Pom, The	雙龍出海	84min
双龙吐珠	动作	Pom Pom Strikes Back	雙龍吐珠	85min
谁当鬼	靈幻	Ghost Snatchers	誰當鬼(俾鬼捉-港譯)	88min
说谎的女人	劇情	I am Sorry	說謊的女人	91min
死亡塔	动作	Tower Of Death	死亡塔	82min
四千金	劇情	Four Loves	四千金	88min
四人新世界	喜劇	When East Goes West	四人新世界(港:黃師傅走天涯)(父子同路)	93min
四眼仔	喜劇	Mummy Dearest	四眼仔(AKA:四眼狐狸)	85min
台上台下	劇情	Send In The Clowns	台上台下	90min
太子爷出差	喜劇	Freedom Run	太子爺出差(AKA:優皮雙嚮炮)	81min
逃学英雄传	喜劇	Truant Heroes	逃學英雄傳            	85min
特级大扫把	劇情	Crazy Romance	特級大掃把(AKA:求愛反斗星)	87min
特警屠龙	动作	Tiger Cage	特警屠龍	88min
提防小手	喜劇	Carry On Pickpocket	提防小手	96min
天赐良缘	喜劇	Sister Cupid	天賜良緣	95min
天地雄心	動作 劇情	Armageddon	天地雄心	108min
天灵灵地灵灵	靈幻	Abracadabra	天靈靈地靈靈	85min
天罗地网	劇情	Gunmen	天羅地網	84min
天若有情3烽火佳人	劇情	Moment Of Romance III, A	天若有情 III 之烽火佳人	95min
天生宝一对	喜劇	Happy Union II	天生寶一對	90min
天生绝配	劇情	Perfect Match	天生絕配	92min
天有眼	劇情	Comeuppance	天有眼	106min
天与地	劇情	Tian Di	天與地	99min
天真有牙	劇情	Daughter & Father	天真有牙	90min
甜蜜十六岁	劇情	Sweet Sixteen	甜蜜十六歲	84min
铁板烧	喜劇	Teppanyaki	鐵板燒	85min
铁甲无敌玛利亚	劇情	I Love Maria	鐵甲無敵瑪利亞(AKA: 鐵甲無敵)	98min
铁血骑警	劇情	Road Warriors	鐵血騎警	89min
听不到的说话	劇情	Silent Love	聽不到的說話	87min
通天大盗	劇情	Easy Money	通天大盜	92min
头号人物	劇情	Headlines	頭號人物	92min
拖错车	喜劇	Cop Busters	拖錯車(公僕難為)	88min
顽皮家族	喜劇	Funny Family	頑皮家族	89min
亡命天涯	劇情	Bitter Taste Of Blood	亡命天涯(aka:英雄血)	87min
亡命鸳鸯	劇情	On The Run	亡命鴛鴦	81min
忘不了	劇情	Lost In Time	忘不了	109min
望夫成龙	劇情	Love Is Love	望夫成龍	91min
危险情人	劇情	Shootout, The	危險情人(港:槍戰)(愛在槍口上-台譯)	93min
危险人物	劇情	Undercover	危險人物	90min
威龙猛探	动作	Protector, The	威龍猛探	85min
为你钟情	劇情	For Your Heart Only	為你鍾情(求愛衝風隊-台譯)	84min
卫斯理传奇	劇情	Legend Of The Wisely, The	卫斯理传奇	85min
卫斯理蓝血人	動作 劇情	Wesley's Mysterious File, The	衛斯理藍血人	87min
我爱777	爱情	My Loving Trouble 7	我愛777	103min
我爱唐人街	劇情	What A Small World	我愛唐人街	88min
我爱夜来香	喜劇	All The Wrong Spies	我愛夜來香	98min
我的麻烦老友	喜劇	My Troublesome Buddy	我的麻煩老友	114min
我的婆婆黄飞鸿	劇情	Kung Fu Master Is My Grandma!	我的婆婆黃飛鴻	93min
我家有一只河东狮	喜劇	Lion Roars, The	我家有一隻河東獅	100min
我要金龟婿	喜劇	Sweet Surrender	我要金龜婿	99min
我愿意	喜劇	I Do	我願意	84min
我在江湖中的日子	劇情	Triads The Inside Story	我在江湖中的日子(港译:我在黑社會的日子)	86min
我左眼见到鬼	恐怖 喜劇	My Left Eye Sees Ghosts	我左眼見到鬼	97min
乌龙大家庭	喜劇	Family Strikes Back, The	烏龍大家庭(AKA:無敵大家庭)	86min
无限复活	剧情/赌博	Second Time Around	無限復活	98min
五个寂寞的心	劇情	Five Lonely Hearts(Lonely Hearts Quintet)	五個寂寞的心 (放電十六-台譯)	86min
午夜丽人	劇情	Midnight Girls	午夜麗人	79min
武状元苏乞儿	喜劇	King Of Beggars *	武狀元蘇乞兒	97min
舞台姐妹	劇情	Stage Door Johnny	舞台姊妹	94min
西环的故事	劇情	Story Of Kennedy Town	西環的故事(AKA:心痛的感覺)	92min
洗黑钱	动作	Tiger Cage II	洗黑錢	92min
喜马拉雅星	喜劇	Himalaya Singh	喜馬拉亞星	92min
细圈仔	劇情	Once Upon A Mirage	細圈仔	95min
夏日福星	喜劇	Twinkle Twinkle Lucky Stars	夏日福星	99min
先生贵姓	喜劇	And Now What Is Your Name?	先生貴姓	87min
旗袍内的秘密（香港小姐写真）	劇情	Private Life (Miss Hong Kong)	香港小姐寫真(aka:旗袍內的秘密)	79min
潇洒先生	喜劇	Mr. Smart	瀟洒先生(男人像霧又像花-台譯)	88min
小白龙情海翻波	動作 喜劇	White Dragon, The	小白龍情海翻波	93min
小飞侠	动作	Teenage Master	小飛俠	89min
小鬼三个爸	喜劇	Daddy, Father And Papa	小鬼三個爸(AKA:老豆唔怕多)	91min
小狐仙	喜劇	Unforgettable Fantasy	小狐仙	95min
小男人周记	劇情	Yuppie Fantasia, The	小男人週記	92min
小生梦惊魂	靈幻	Scared Stiff	小生夢驚魂(小生驚驚)	86min
小生怕怕	喜劇	Till Death Do We Scare	小生怕怕	88min
小心间谍	劇情	To Spy With Love	小心間諜	93min
小心眼	恐怖	The Third Eye	小心眼	88min
校墓处	恐怖	The Haunted School	校墓處	84min
笑傲江湖	劇情	Swordsman	笑傲江湖	112min
笑匠	喜劇	In The Time You Need A Friend	笑匠	91min
笑声撞地球	喜劇	Sunshine Friends	笑声撞地球(笑聲撞地球)	89min
蝎子战士	动作	Operation Scorpio(Palette:港譯)	蝎子战士(aka:蠍子王)	96min
心动	劇情	Walking Beside Me	心動	90min
心跳一百	喜劇	Heartbeat 100	心跳一百(AKA:鬼屋有寶)	89min
新上海滩	動作 劇情	Shanghai Grand	新上海灘 	92min
新最佳拍档	喜劇	Aces Go Places V	新最佳拍檔(新最佳拍檔兵馬風雲-台譯)	98min
凶猫	劇情	Evil Cat	凶貓	86min
凶男寡女	恐怖	Set up	凶男寡女	95min
兄弟	动作	Brotherhood	兄弟	81min
学校风云	劇情	School On Fire	學校風雲	95min
血玫瑰	劇情	Her Vengeance	血玫瑰	84min
血衣天使	劇情	Vengeance Is Mine	血衣天使	85min
巡城马	动作	Postman Fights Back, The	巡城馬	85min
胭脂扣	劇情	Rouge	胭脂扣	93min
野兽之瞳	動作 劇情	Born Wild	野獸之瞳	109min
夜疯狂	劇情	All Night Long	夜瘋狂	91min
夜惊魂	喜劇	He Lives By Night	夜驚魂 (心跳一百-台譯)	86min
夜生活女王霞姐传奇	劇情	Queen Of Underworld	夜生活女王霞姐傳奇	80min
夜夜伴肥娇	喜劇	Changing Partner	夜夜伴肥嬌(AKA:親親拍檔)	80min
一触即发	动作	Point Of No Return	一觸即發	93min
一对活宝跑天下	劇情	Wily Match, A	一對活寶跑天下	88min
一哥	动作	Big Brother, The	一哥	88min
一眉道人	靈幻	Vampire Vs Vampire	一眉道人	84min
一妻两夫	劇情	One Husband Too Many	一妻兩夫(aka:一屋兩夫)	87min
一世好命	喜劇	You Bet Your Life (Charmed Life, A)	一世好命	90min
一屋两妻	劇情	Happy Bigamist, The	一屋兩妻(一屋二妻)	85min
一咬ok	劇情	Bite Of Love, A	一咬OK	89min
一招半式闯江湖	动作	Half A Loaf Of Kung Fu	一招半式闖江湖	94min
伊人再见	喜劇	Silent Romance	伊人再見	92min
义本无言	劇情	Code of Honour	義本無言	94min
义胆红唇	动作	City War	義膽紅唇(AKA:城市戰爭)	91min
义盖云天	动作	Hearty Response, A	義蓋雲天	85min
阴阳错	喜劇	Espirit D'Amour	陰陽錯	88min
阴阳路之升官发财	恐怖	Troublesome Night III	陰陽路之升棺發財	99min
阴阳路之我在你左右	恐怖	Troublesome Night II	陰陽路之我在你左右	96min
阴阳奇兵	恐怖	Young Taoism Fighter, The	陰陽奇兵	94min
英伦琵琶	劇情	Banana Cop	英倫琵琶(AKA:香蕉探長)	92min
英雄本色	动作	Better Tomorrow, A	英雄本色	92min
英雄本色续集	动作	Better Tomorrow II, A	英雄本色續集	99min
英雄无泪	动作	Hero Shed No Tears	英雄無淚(AKA:英雄本性)	77min
英雄正传	劇情	True Colours	英雄正傳(AKA:英雄偶像)	84min
雍正与年羹尧	动作	Rebellious Reigh, The	雍正與年羹堯	86min
勇者无惧	动作	Dreadnaught	勇者無懼	92min
又见冤家	劇情	Love Me and My Dad	又見冤家	94min
与龙共舞	喜劇	Dances With Dragon *	與龍共舞	104min
郁达夫传奇	劇情	When Tat Fu Was Young	郁達夫傳奇(AKA:少年郁達夫)	91min
狱凤之还我清白	动作	On Parole	獄鳳之還我清白(港译:獄鳳)	80min
原振侠与卫斯理	动作	Seventh Curse, The	原振俠與衛斯理	75min
缘分新天空	爱情	Give Love A Chance	緣份新天空	89min
月亮星星太阳	劇情	Moon Stars & Sun	月亮星星太陽	93min
再见王老五	劇情	Bachelor's Swan Song, The	再見王老五	94min
再起风云	劇情	Last Duel, The	再起風雲	97min
再生人	劇情	Life After Life	再生人	80min
贼赃	动作	Loot, The	賊贓	94min
这个阿爸真爆炸	喜劇	Pa Pa Loves You	這個阿爸真爆炸	100min
执法先锋	动作	Righting Wrongs	執法先鋒	93min
中国最后一个太监	劇情	Lai Shi China's Last Eunuch	中國最後一個太監	85min
中华战士	动作	Magnificent Warriors	中華戰士	83min
中间人	动作	Middle Man	中間人	90min
钟无艳	喜劇	Wu Yen	鍾無艷	123min
猪标一族	喜劇	Best Friend Of The Cops	豬標一族 (霹靂狐-台譯)	90min
祝您好运	劇情	Lucky Diamond	祝您好運	92min
抓鬼特攻队	喜劇	Ghost Bustin	抓鬼特攻隊	90min
专撬墙角	喜劇	Perfect Wife, The	專撬牆腳(AKA:拉後腳)	90min
壮志雄心	动作	Thank You, Sir	壯志雄心	91min
追女仔	喜劇	Chasing Girls	追女仔 (泡妞-台譯)	93min
孖宝闯八关	动作	Read Lips	孖寶闖八關	91min
纵横四海	劇情	Once A Thief	縱橫四海	96min
最后胜利	劇情	Final Victory	最後勝利	89min
最后一战	动作	Final Test, The	最後一戰	86min
最佳福星	喜劇	Lucky Stars Go Places	最佳福星	96min
最佳女婿	喜劇	Faithfully Yours	最佳女婿	88min
最佳拍档	喜劇	Aces Go Places I	最佳拍檔 (光頭神探賊狀元-台譯)	90min
最佳拍档大显神通	喜劇	Aces Go Places II	最佳拍檔大顯神通	97min
最佳拍档女皇密令	喜劇	Aces Go Places III	最佳拍檔女皇密令	92min
最佳拍档之千里救差婆	喜劇	Aces Go Places IV	最佳拍檔之千里救差婆	85min
EOF;
        return explode(PHP_EOL, $str);
    }


    private function parseTag($w)
    {
        $tags = [
            '动作'=> 'action','喜劇'=> 'comedy','劇情'=> 'story','恐怖'=> 'horror',
            '靈幻'=> 'fantacy', '爱情'=>'romance','動作'=>'action','惊悚'=>'thriller',
            '武俠'=>'kongfu','赌博'=>'gambling'
        ];
        return $w?$tags[$w]:'';
    }
    
}
