<?php

require_once(__DIR__.'/RRDBase.php');

class RRDCpu extends RRDBase {

    protected function touchGraph()
    {
        $rrdFile = $this->path . DIRECTORY_SEPARATOR . 'mpstat.rrd';
        if (!file_exists($rrdFile)) {
            $this->debug("creating [$rrdFile]\n");
            if (!rrd_create($rrdFile, [
                "-s",60,
                "DS:usr:GAUGE:120:0:100",
                "DS:nice:GAUGE:120:0:100",
                "DS:sys:GAUGE:120:0:100",
                "DS:iowait:GAUGE:120:0:100",
                "DS:irq:GAUGE:120:0:100",
                "DS:soft:GAUGE:120:0:100",
                "DS:steal:GAUGE:120:0:100",
                "DS:guest:GAUGE:120:0:100",
                "DS:gnice:GAUGE:120:0:100",
                "DS:idle:GAUGE:120:0:100",
                "RRA:AVERAGE:0.5:1:2880",
                "RRA:AVERAGE:0.5:30:672",
                "RRA:AVERAGE:0.5:120:732",
                "RRA:AVERAGE:0.5:720:1460"
            ])){
                $this->fail(rrd_error());
            }
            $this->debug("Created [$rrdFile]\n");
        }
    }

    public function collect()
    {
        $tmpPathName = $this->path . DIRECTORY_SEPARATOR . "mpstat.tmp";
        $cmd = "mpstat {$this->interval} 1 > " . $tmpPathName;
        $this->debug("Executing: [$cmd]\n");
        exec($cmd);
        if (!file_exists($tmpPathName)) {
            $this->fail("The file [$tmpPathName] could not be found.");
        }
        $stat = file_get_contents($tmpPathName);
        $values = false;

        // @see http://man7.org/linux/man-pages/man1/mpstat.1.html
        $keys = [
            'usr',
            'nice',
            'sys',
            'iowait',
            'irq',
            'soft',
            'steal',
            'guest',
            'gnice',
            'idle'
        ];

        foreach (explode("\n", $stat) as $row) {
            if (preg_match('/Average.*all([ .0-9]+)/', $row, $matches) === 1) {
                $values = explode(' ', preg_replace('!\s+!', ' ', $matches[1]));
                $values = array_filter($values, function($e){ return !empty($e); });
                break;
            }
        }

        if ($values === false || (is_array($values) && count($values) !== count($keys))){
            $this->fail("Error parsing output of mpstat 1 1\n");
        }

        $rrdFile = $this->path . DIRECTORY_SEPARATOR . 'mpstat.rrd';

        if (!rrd_update($rrdFile, [
            "-t",
            implode(':', $keys),
            "N:" . implode(':', $values)
        ])){
            $this->fail(rrd_error());
        }
        unlink($tmpPathName);
    }

    public function graph($period = 'day', $graphPath = __DIR__)
    {
        if (!file_exists($graphPath)) {
            $this->fail("The path [$graphPath] does not exist or is not readable.\n");
        }

        $rrdFile = $this->path . DIRECTORY_SEPARATOR . 'mpstat.rrd';

        if(!rrd_graph($graphPath . '/cpu_usage_' . $period . '.png', [
            "-s","-1$period",
            "-t CPU Usage ($period)",
            "--lazy",
            "-h", "150", "-w", "700",
            "-l 0",
            "-a", "PNG",
            "-v Processor Usage %",
            "--units-exponent=0",
            "--upper-limit=100",
            "--lower-limit=0",
            "--rigid",
            "DEF:usr=$rrdFile:usr:AVERAGE",
            "DEF:nice=$rrdFile:nice:AVERAGE",
            "DEF:sys=$rrdFile:sys:AVERAGE",
            "DEF:iowait=$rrdFile:iowait:AVERAGE",
            "DEF:irq=$rrdFile:irq:AVERAGE",
            "DEF:soft=$rrdFile:soft:AVERAGE",
            "DEF:steal=$rrdFile:steal:AVERAGE",
            "DEF:guest=$rrdFile:guest:AVERAGE",
            "DEF:gnice=$rrdFile:gnice:AVERAGE",
            "DEF:idle=$rrdFile:idle:AVERAGE",

            "AREA:usr#2C3E50:User:STACK",               // *
            "GPRINT:usr:LAST:     Current\\: %5.2lf%%",
            "GPRINT:usr:MIN:Min\\: %5.2lf%%",
            "GPRINT:usr:AVERAGE:Average\\: %5.2lf%%",
            "GPRINT:usr:MAX:Max\\: %5.2lf%%\\n",

            "AREA:nice#0EAD9A:Nice:STACK",              // *
            "GPRINT:nice:LAST:     Current\\: %5.2lf%%",
            "GPRINT:nice:MIN:Min\\: %5.2lf%%",
            "GPRINT:nice:AVERAGE:Average\\: %5.2lf%%",
            "GPRINT:nice:MAX:Max\\: %5.2lf%%\\n",

            "AREA:sys#F8C82D:System:STACK",             // *
            "GPRINT:sys:LAST:   Current\\: %5.2lf%%",
            "GPRINT:sys:MIN:Min\\: %5.2lf%%",
            "GPRINT:sys:AVERAGE:Average\\: %5.2lf%%",
            "GPRINT:sys:MAX:Max\\: %5.2lf%%\\n",

            "AREA:iowait#E84B3A:IO Wait:STACK",         // *
            "GPRINT:iowait:LAST:  Current\\: %5.2lf%%",
            "GPRINT:iowait:MIN:Min\\: %5.2lf%%",
            "GPRINT:iowait:AVERAGE:Average\\: %5.2lf%%",
            "GPRINT:iowait:MAX:Max\\: %5.2lf%%\\n",

            "AREA:irq#832D51:IRQ:STACK",                // *
            "GPRINT:irq:LAST:      Current\\: %5.2lf%%",
            "GPRINT:irq:MIN:Min\\: %5.2lf%%",
            "GPRINT:irq:AVERAGE:Average\\: %5.2lf%%",
            "GPRINT:irq:MAX:Max\\: %5.2lf%%\\n",

            "AREA:soft#74525F:Soft IRQ:STACK",          // *
            "GPRINT:soft:LAST: Current\\: %5.2lf%%",
            "GPRINT:soft:MIN:Min\\: %5.2lf%%",
            "GPRINT:soft:AVERAGE:Average\\: %5.2lf%%",
            "GPRINT:soft:MAX:Max\\: %5.2lf%%\\n",

            "AREA:steal#404148:Steal:STACK",            // *
            "GPRINT:steal:LAST:    Current\\: %5.2lf%%",
            "GPRINT:steal:MIN:Min\\: %5.2lf%%",
            "GPRINT:steal:AVERAGE:Average\\: %5.2lf%%",
            "GPRINT:steal:MAX:Max\\: %5.2lf%%\\n",

            "AREA:gnice#6EC198:GNice:STACK",
            "GPRINT:gnice:LAST:    Current\\: %5.2lf%%",
            "GPRINT:gnice:MIN:Min\\: %5.2lf%%",
            "GPRINT:gnice:AVERAGE:Average\\: %5.2lf%%",
            "GPRINT:gnice:MAX:Max\\: %5.2lf%%\\n",

            "AREA:idle#27AE60:Idle:STACK",              // *
            "GPRINT:idle:LAST:     Current\\: %5.2lf%%",
            "GPRINT:idle:MIN:Min\\: %5.2lf%%",
            "GPRINT:idle:AVERAGE:Average\\: %5.2lf%%",
            "GPRINT:idle:MAX:Max\\: %5.2lf%%\\n",

            "HRULE:0#000000"

        ])) {
            $this->fail('Error writing connections graph for period '. $period  .' ['. rrd_error() .']');
        }
    }
}

$p = new RRDCpu(__DIR__, 1, true);
$p->collect();
$p->graph('hour', __DIR__ . '/../httpdocs/img');
$p->graph('day', __DIR__ . '/../httpdocs/img');
$p->graph('week', __DIR__ . '/../httpdocs/img');
$p->graph('month', __DIR__ . '/../httpdocs/img');
$p->graph('year', __DIR__ . '/../httpdocs/img');
