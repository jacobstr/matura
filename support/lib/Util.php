<?php namespace Matura\Test;

class Util
{
    public static function gensuite($config = array(), $current_depth = 1)
    {
        $config = array_merge(array(
            'befores' => 0,
            'before_alls' => 0,
            'afters' => 0,
            'after_alls' => 0,
            'tests' => 1,
            'depth' => 0,
            'describes' => array('L', 'R'),
            'callbacks' => array(
                'it' => function ($ctx) {
                    expect(true)->to->eql(true);
                },
                'before' => function ($ctx) {
                    $ctx->value = 3;
                },
                'before_all' => function ($ctx) {
                    $ctx->value = 5;
                },
                'after' => function ($ctx) {
                    $ctx->value = 7;
                },
                'after_all' => function ($ctx) {
                    $ctx->value = 11;
                }
            )
        ), $config);

        if ($config['depth'] == 0) {
            return;
        }

        foreach($config['describes'] as $side) {
            describe("Level {$side}{$current_depth}", function ($ctx) use (
                $config,
                $current_depth
            )
            {
                for ($i = 1; $i <= $config['tests']; $i++) {
                    it("nested $i", $config['callbacks']['it']);
                }

                for ($i = 1; $i <= $config['befores']; $i++) {
                    before($config['callbacks']['before']);
                }

                for ($i = 1; $i <= $config['before_alls']; $i++) {
                    before_all($config['callbacks']['before_all']);
                }

                for ($i = 1; $i <= $config['after_alls']; $i++) {
                    after_all($config['callbacks']['after_all']);
                }

                for ($i = 1; $i <= $config['afters']; $i++) {
                    after($config['callbacks']['after']);
                }

                $config['depth']--;

                Util::gensuite($config, $current_depth + 1);
            });
        }
    }
}
