<?php
/**
 * DVYS AI - Keepalive Endpoint
 * 
 * Ping cette URL toutes les 5 minutes pour éviter le cold start de Render.
 * Services gratuits recommandés : UptimeRobot (https://uptimerobot.com), Cron-job.org
 * 
 * URL à surveiller : https://dvys.onrender.com/keepalive.php
 */
header('Content-Type: text/plain');
echo 'pong';
