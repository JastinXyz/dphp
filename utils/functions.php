<?php

function setupMinigamesDescription($d, $isEnd = false) {
    usort($d, function ($a, $b) {
        return $b["poin"] - $a["poin"];
    });

    $desc = "";
    for ($i = 0; $i < count($d); $i++) {
        $playerId = $d[$i]['player'];
        $playerPoin = $d[$i]['poin'];
        
        if($isEnd) {
            if ($i === 0) {
                $desc .= "<@$playerId> **[$playerPoin]** 🎉\n";
            } else {
                $desc .= "<@$playerId> **[$playerPoin]**\n";
            }
        } else {
            $desc .= "<@$playerId> **[$playerPoin]**\n";
        }
    }

    return $desc;
}