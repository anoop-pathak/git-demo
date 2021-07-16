<?php
use Illuminate\Database\Seeder;
use App\Models\Template;
use Illuminate\Support\Facades\DB;

class TemplatesTableSeeder extends Seeder
{

    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Template::truncate();

        Template::create([
            'type' => 'blank',
            'title' => 'Blank Template',
            'content' => '<div class="dropzone-container" style="background-color:;"> <ul dnd-list="list" class="section template-section"> <!-- ngRepeat: item in list --><!-- ngInclude: item.type + \'.html\' --><li draggable="false" ng-repeat="item in list" class="title" dnd-draggable="item" dnd-effect-allowed="copyMove" dnd-dragstart="showDelete(\'show\', event)" dnd-moved="list.splice($index, 1); showDelete(\'hide\', event)" dnd-selected="models.selected = item" dnd-disable-if="$index === 0" ng-class="{\'selected\': models.selected === item, \'label\':(item.type === \'switch\') }" ng-include="item.type + \'.html\'"></li><!-- end ngRepeat: item in list --> </ul> </div>',
            'thumb' => 'templates/1_template.jpg'
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
