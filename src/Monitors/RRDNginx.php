<?php

namespace Carbontwelve\Monitor\Monitors;

// https://easyengine.io/tutorials/nginx/status-page/

class RRDNginx extends RRDBase
{
    protected $rrdFileName = 'nginx.rrd';

    protected $graphName = 'requests_%period%.png';

    protected $configuration = [
        'statsUrl' => ''
    ];

    protected function configurationLoaded()
    {
        if (empty($this->configuration['statsUrl'])) {
            return false;
        }
        return parent::configurationLoaded();
    }

    public function touchGraph()
    {
        if (!file_exists($this->rrdFilePath)) {
            $this->debug("Creating [$this->rrdFilePath]\n");
            if (!rrd_create($this->rrdFilePath, [
                "-s",60,
                "DS:requests:COUNTER:120:0:100000000",
                "DS:total:ABSOLUTE:120:0:60000",
                "DS:reading:ABSOLUTE:120:0:60000",
                "DS:writing:ABSOLUTE:120:0:60000",
                "DS:waiting:ABSOLUTE:120:0:60000",
                "RRA:AVERAGE:0.5:1:2880",
                "RRA:AVERAGE:0.5:30:672",
                "RRA:AVERAGE:0.5:120:732",
                "RRA:AVERAGE:0.5:720:1460"
            ])){
                $this->fail(rrd_error());
            }
            $this->debug("Created [$this->rrdFilePath]\n");
        }
    }

    public function collect()
    {
        $requests = $total = $reading = $writing = $waiting = 0;

        $stat = file_get_contents($this->configuration['statsUrl']);
        foreach (explode("\n", $stat) as $row) {
            if (preg_match('/^Active connections:\s+(\d+)/', $row, $matches) === 1) {
                $total = $matches[1];
                continue;
            }

            if (preg_match('/^Reading:\s+(\d+).*Writing:\s+(\d+).*Waiting:\s+(\d+)/', $row, $matches) === 1) {
                $reading = $matches[1];
                $writing = $matches[2];
                $waiting = $matches[3];
                continue;
            }

            if (preg_match('/^\s+(\d+)\s+(\d+)\s+(\d+)/', $row, $matches) === 1) {
                $requests = $matches[3];
                continue;
            }
        }

        $this->debug("Total: ($total), Requests: ($requests), Reading: ($reading), Writing: ($writing), Waiting ($waiting)\n");

        if (!rrd_update($this->rrdFilePath, [
            "-t",
            "requests:total:reading:writing:waiting",
            "N:$requests:$total:$reading:$writing:$waiting"
        ])){
            $this->fail(rrd_error());
        }
    }

    public function graph($period = 'day', $graphPath = __DIR__)
    {
        if (!file_exists($graphPath)) {
            $this->fail("The path [$graphPath] does not exist or is not readable.\n");
        }

        if(!rrd_graph($graphPath . DIRECTORY_SEPARATOR . $this->getGraphName($period), [
            "-s","-1$period",
            "-t Nginx Requests in the last $period",
            "--lazy",
            "-h", "150", "-w", "700",
            "-l 0",
            "-r",
            "-a", "PNG",
            "-v requests/sec",
            "-Y",
            "--units-exponent=0",
            "--pango-markup",

            "DEF:requests=".$this->rrdFilePath.":requests:AVERAGE",
            "LINE2:requests#27AE60:Requests",
            "GPRINT:requests:MAX:  Max\\: %5.2lf",
            "GPRINT:requests:AVERAGE: Avg\\: %5.2lf",
            "GPRINT:requests:LAST: Current\\: %5.2lf req/sec\\n",

            "HRULE:0#000000",

            'COMMENT:<span foreground="#ABABAB" size="x-small">'. date('D M jS H') . '\:' . date('i') . '\:' . date('s') .'</span>\r'
        ])) {
            $this->fail('Error writing requests graph for period '. $period  .' ['. rrd_error() .']');
        }

        if(!rrd_graph($graphPath . '/connections_' . $period . '.png', [
            "-s","-1$period",
            "-t Nginx Connections in the last $period",
            "--lazy",
            "-h", "150", "-w", "700",
            "-l 0",
            "-r",
            "-a", "PNG",
            "-v requests/sec",
            "-Y",
            "--units-exponent=0",
            "--pango-markup",

            "DEF:total={$this->rrdFilePath}:total:AVERAGE",
            "DEF:reading={$this->rrdFilePath}:reading:AVERAGE",
            "DEF:writing={$this->rrdFilePath}:writing:AVERAGE",
            "DEF:waiting={$this->rrdFilePath}:waiting:AVERAGE",

            "LINE2:total#27AE60:Total",
            "GPRINT:total:LAST:   Current\\: %5.2lf",
            "GPRINT:total:MIN:  Min\\: %5.2lf",
            "GPRINT:total:AVERAGE: Avg\\: %5.2lf",
            "GPRINT:total:MAX:  Max\\: %5.2lf\\n",

            "LINE2:reading#2C3E50:Reading",
            "GPRINT:reading:LAST: Current\\: %5.2lf",
            "GPRINT:reading:MIN:  Min\\: %5.2lf",
            "GPRINT:reading:AVERAGE: Avg\\: %5.2lf",
            "GPRINT:reading:MAX:  Max\\: %5.2lf\\n",

            "LINE2:writing#E84B3A:Writing",
            "GPRINT:writing:LAST: Current\\: %5.2lf",
            "GPRINT:writing:MIN:  Min\\: %5.2lf",
            "GPRINT:writing:AVERAGE: Avg\\: %5.2lf",
            "GPRINT:writing:MAX:  Max\\: %5.2lf\\n",

            "LINE2:waiting#F8C82D:Waiting",
            "GPRINT:waiting:LAST: Current\\: %5.2lf",
            "GPRINT:waiting:MIN:  Min\\: %5.2lf",
            "GPRINT:waiting:AVERAGE: Avg\\: %5.2lf",
            "GPRINT:waiting:MAX:  Max\\: %5.2lf\\n",

            "HRULE:0#000000",

            'COMMENT:<span foreground="#ABABAB" size="x-small">'. date('D M jS H') . '\:' . date('i') . '\:' . date('s') .'</span>\r'

        ])) {
            $this->fail('Error writing connections graph for period '. $period  .' ['. rrd_error() .']');
        }
    }

}

// $p = new RRDNginx(__DIR__, true);
// $p->setNginxStatsUrl('http://127.0.0.1/nginx_status');
// $p->collect();
// $p->graph('hour', __DIR__ . '/../httpdocs/img');
// $p->graph('day', __DIR__ . '/../httpdocs/img');
// $p->graph('week', __DIR__ . '/../httpdocs/img');
// $p->graph('month', __DIR__ . '/../httpdocs/img');
// $p->graph('year', __DIR__ . '/../httpdocs/img');

// file_put_contents($logPath . '/timestamp.js', 'function getTimestamp(){ return '.json_encode(['date' => date('c')]).'; }');
