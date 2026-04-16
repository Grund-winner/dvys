<?php
/**
 * DVYS AI - Moteur de Prédiction Avancé v2.0
 * 
 * Algorithme multi-couches d'analyse de patterns pour jeux crash.
 * 8 modules d'analyse combinés avec pondération adaptative.
 * 
 * Modules :
 *  1. Analyse de Tendance (EMA/SMA)
 *  2. Détection de Séries (Streak)
 *  3. Analyse de Volatilité (Bollinger)
 *  4. Pattern Matching (N-grams)
 *  5. Chaîne de Markov (Transitions)
 *  6. Réversion à la Moyenne
 *  7. Détection de Cycles
 *  8. Score de Momentum
 */

class CrashPredictor
{
    /** @var array Historique brut des multiplicateurs (du plus ancien au plus récent) */
    private array $history;

    /** @var float Plancher minimum de prédiction */
    private float $minPred;

    /** @var float Plafond maximum de prédiction */
    private float $maxPred;

    /** @var int Nombre minimum de rounds requis pour une analyse fiable */
    private const MIN_ROUNDS = 5;

    /** @var array Poids de chaque module (somme = 1.0) */
    private array $weights = [
        'trend'        => 0.12,
        'streak'       => 0.20,
        'volatility'   => 0.08,
        'pattern'      => 0.16,
        'markov'       => 0.16,
        'meanReversion'=> 0.10,
        'cycle'        => 0.06,
        'momentum'     => 0.12,
    ];

    /** @var array Résultats de chaque module */
    private array $moduleResults = [];

    // ================================================================
    //  CONSTRUCTEUR
    // ================================================================

    public function __construct(array $history, float $minPred = 1.00, float $maxPred = 25.00)
    {
        // Nettoyer et filtrer l'historique
        $this->history = array_values(array_filter(
            $history,
            fn($v) => is_numeric($v) && $v > 0
        ));
        $this->minPred = $minPred;
        $this->maxPred = $maxPred;
    }

    // ================================================================
    //  INTERFACE PRINCIPALE
    // ================================================================

    /**
     * Point d'entrée principal — exécute tous les modules et retourne la prédiction.
     * 
     * @return array{
     *     prediction: float,
     *     confidence: float,
     *     modules: array,
     *     signals: string[],
     *     analysis: array
     * }
     */
    public function predict(): array
    {
        // Si pas assez de données, retourner une valeur par défaut
        if (count($this->history) < self::MIN_ROUNDS) {
            return $this->fallbackPrediction();
        }

        // Exécuter les 8 modules d'analyse
        $this->moduleResults['trend']         = $this->analyzeTrend();
        $this->moduleResults['streak']        = $this->analyzeStreak();
        $this->moduleResults['volatility']    = $this->analyzeVolatility();
        $this->moduleResults['pattern']       = $this->analyzePatterns();
        $this->moduleResults['markov']        = $this->analyzeMarkov();
        $this->moduleResults['meanReversion'] = $this->analyzeMeanReversion();
        $this->moduleResults['cycle']         = $this->analyzeCycles();
        $this->moduleResults['momentum']      = $this->analyzeMomentum();

        // Combiner les scores avec pondération adaptative
        $weights = $this->adaptWeights();
        $combined = $this->combineModules($weights);

        // Générer la prédiction finale
        $prediction = $this->generateFinalPrediction($combined);

        // Collecter les signaux détectés
        $signals = $this->collectSignals();

        return [
            'prediction' => round($prediction, 2),
            'confidence' => round($combined['confidence'], 2),
            'modules'    => $this->summarizeModules(),
            'signals'    => $signals,
            'analysis'   => [
                'rounds_analyzed'  => count($this->history),
                'avg'              => round($this->avg($this->history), 2),
                'std_dev'          => round($this->stdDev($this->history), 2),
                'last_5'           => array_slice($this->history, -5),
                'direction'        => $combined['direction'],
            ],
        ];
    }

    // ================================================================
    //  MODULE 1 : ANALYSE DE TENDANCE (EMA / SMA)
    // ================================================================

    private function analyzeTrend(): array
    {
        $h = $this->history;
        $n = count($h);

        // Calculer les moyennes mobiles
        $ema5  = $this->ema($h, 5);
        $ema10 = $n >= 10 ? $this->ema($h, 10) : $ema5;
        $ema20 = $n >= 20 ? $this->ema($h, 20) : $ema10;
        $sma10 = $n >= 10 ? $this->sma($h, 10) : $ema5;

        // Signal de tendance
        $shortAboveLong = $ema5 > $ema10;
        $macd = $ema5 - $ema10;

        // Déterminer la direction et la force
        $direction = 0; // -1 bearish, 0 neutre, +1 bullish
        $strength = 0;

        if ($macd > 0.3) {
            $direction = 1;
            $strength = min(abs($macd) / 2.0, 1.0);
        } elseif ($macd < -0.3) {
            $direction = -1;
            $strength = min(abs($macd) / 2.0, 1.0);
        }

        // Si la tendance monte, prédire modéré-haut
        // Si la tendance descend, prédire bas
        $suggested = $ema5 + ($macd * 0.3);

        return [
            'suggested'  => $this->clamp($suggested),
            'direction'  => $direction,
            'strength'   => $strength,
            'confidence' => min(0.5 + $strength * 0.3, 0.9),
            'ema5'       => round($ema5, 3),
            'ema10'      => round($ema10, 3),
            'macd'       => round($macd, 3),
        ];
    }

    // ================================================================
    //  MODULE 2 : DÉTECTION DE SÉRIES (STREAK)
    // ================================================================

    private function analyzeStreak(): array
    {
        $h = $this->history;
        $categories = $this->categorize($h);

        // Détecter la série en cours
        $last = $categories[count($categories) - 1];
        $streakLen = 1;
        for ($i = count($categories) - 2; $i >= 0; $i--) {
            if ($categories[$i] === $last) {
                $streakLen++;
            } else {
                break;
            }
        }

        // Distribution des catégories (derniers 20 rounds)
        $recent = array_slice($categories, -20);
        $counts = array_count_values($recent);
        $total = count($recent);

        $lowPct  = ($counts['low'] ?? 0) / $total;
        $midPct  = ($counts['mid'] ?? 0) / $total;
        $highPct = ($counts['high'] ?? 0) / $total;

        // Logique de prédiction basée sur les séries
        $suggested = 0;
        $confidence = 0.5;
        $signal = '';

        if ($streakLen >= 4) {
            // Longue série → forte probabilité de cassure
            if ($last === 'low') {
                $suggested = $this->randomInRange(2.50, 5.50);
                $confidence = 0.75 + min($streakLen * 0.03, 0.15);
                $signal = "long_low_streak_break";
            } elseif ($last === 'high') {
                $suggested = $this->randomInRange(1.20, 2.20);
                $confidence = 0.70 + min($streakLen * 0.03, 0.15);
                $signal = "long_high_streak_correction";
            } else {
                $suggested = $this->randomInRange(1.30, 2.50);
                $confidence = 0.60;
                $signal = "long_mid_streak_end";
            }
        } elseif ($streakLen === 3) {
            if ($last === 'low') {
                $suggested = $this->randomInRange(2.00, 4.50);
                $confidence = 0.65;
                $signal = "triple_low_rebound";
            } elseif ($last === 'high') {
                $suggested = $this->randomInRange(1.20, 2.00);
                $confidence = 0.60;
                $signal = "triple_high_pullback";
            } else {
                $suggested = $this->randomInRange(1.50, 3.00);
                $confidence = 0.55;
                $signal = "triple_mid_transition";
            }
        } elseif ($streakLen === 2) {
            if ($last === 'low') {
                $suggested = $this->randomInRange(1.80, 3.50);
                $confidence = 0.55;
                $signal = "double_low_possible_rebound";
            } elseif ($last === 'high') {
                $suggested = $this->randomInRange(1.30, 2.30);
                $confidence = 0.55;
                $signal = "double_high_cooling";
            } else {
                $suggested = $this->randomInRange(1.60, 2.80);
                $confidence = 0.50;
                $signal = "double_mid_continue";
            }
        } else {
            // Pas de série notable → analyser la distribution
            if ($lowPct > 0.65) {
                $suggested = $this->randomInRange(2.00, 4.00);
                $confidence = 0.60;
                $signal = "low_dominance_rebound";
            } elseif ($highPct > 0.40) {
                $suggested = $this->randomInRange(1.30, 2.50);
                $confidence = 0.55;
                $signal = "high_dominance_correction";
            } else {
                $suggested = $this->randomInRange(1.50, 3.00);
                $confidence = 0.45;
                $signal = "no_streak_normal";
            }
        }

        return [
            'suggested'        => $this->clamp($suggested),
            'confidence'       => $confidence,
            'signal'           => $signal,
            'streak_length'    => $streakLen,
            'streak_category'  => $last,
            'low_pct'          => round($lowPct, 3),
            'mid_pct'          => round($midPct, 3),
            'high_pct'         => round($highPct, 3),
        ];
    }

    // ================================================================
    //  MODULE 3 : ANALYSE DE VOLATILITÉ (Bollinger-like)
    // ================================================================

    private function analyzeVolatility(): array
    {
        $h = array_slice($this->history, -20);
        $n = count($h);

        if ($n < 5) {
            return ['suggested' => $this->clamp(2.0), 'confidence' => 0.3, 'regime' => 'unknown'];
        }

        $mean = $this->avg($h);
        $std  = $this->stdDev($h);

        // Bandes de Bollinger
        $upperBand = $mean + (2 * $std);
        $lowerBand = $mean - (2 * $std);

        // Dernière valeur
        $last = $h[$n - 1];

        // Position relative (%B)
        $percentB = $std > 0 ? ($last - $lowerBand) / ($upperBand - $lowerBand) : 0.5;

        // Déterminer le régime de volatilité
        $regime = 'normal';
        if ($std < 0.5) $regime = 'low';
        elseif ($std > 2.0) $regime = 'high';

        // Logique de prédiction
        $suggested = 0;
        $confidence = 0.5;

        if ($percentB < 0.15) {
            // Proche de la bande basse → rebond probable
            $suggested = $mean + ($std * 0.5);
            $confidence = 0.60;
        } elseif ($percentB > 0.85) {
            // Proche de la bande haute → retour probable
            $suggested = $mean - ($std * 0.2);
            $confidence = 0.55;
        } else {
            // Zone normale → prédire autour de la moyenne
            $suggested = $mean + ($this->randomInRange(-0.3, 0.5) * $std);
            $confidence = 0.45;
        }

        // Ajuster selon le régime
        if ($regime === 'low') {
            $suggested += 0.2; // Légèrement plus haut en basse volatilité
            $confidence += 0.05;
        } elseif ($regime === 'high') {
            $suggested = $mean; // Retour à la moyenne en haute volatilité
            $confidence -= 0.05;
        }

        return [
            'suggested'  => $this->clamp($suggested),
            'confidence' => $this->clampConf($confidence),
            'regime'     => $regime,
            'std_dev'    => round($std, 3),
            'upper_band' => round($upperBand, 3),
            'lower_band' => round($lowerBand, 3),
            'percent_b'  => round($percentB, 3),
        ];
    }

    // ================================================================
    //  MODULE 4 : PATTERN MATCHING (N-grams)
    // ================================================================

    private function analyzePatterns(): array
    {
        $h = $this->history;
        $n = count($h);

        if ($n < 10) {
            return ['suggested' => $this->clamp(2.0), 'confidence' => 0.3, 'matched_pattern' => null];
        }

        $cats = $this->categorize($h);
        $bestMatch = null;
        $bestConfidence = 0;
        $predictedCat = 'mid';

        // Tester les patterns de longueur 2, 3, 4 et 5
        foreach ([2, 3, 4, 5] as $len) {
            if ($n < $len * 3) continue; // Pas assez de données pour ce pattern

            $currentPattern = array_slice($cats, -$len);

            // Chercher ce pattern dans l'historique
            $nextValues = [];

            for ($i = 0; $i <= $n - $len - 1; $i++) {
                $window = array_slice($cats, $i, $len);
                if ($window === $currentPattern && $i + $len < $n) {
                    $nextValues[] = $cats[$i + $len];
                }
            }

            if (count($nextValues) >= 2) {
                // Calculer la distribution des catégories suivantes
                $freq = array_count_values($nextValues);
                arsort($freq);
                $topCat = array_key_first($freq);
                $matchRate = $freq[$topCat] / count($nextValues);
                $weight = $len * 0.05; // Les patterns plus longs sont plus fiables
                $score = $matchRate * $weight;

                if ($score > $bestConfidence) {
                    $bestConfidence = $score;
                    $bestMatch = $len . '-gram: ' . implode(',', $currentPattern);
                    $predictedCat = $topCat;
                }
            }
        }

        // Convertir la catégorie prédite en multiplicateur
        $suggested = match ($predictedCat) {
            'low'  => $this->randomInRange(1.20, 1.90),
            'mid'  => $this->randomInRange(2.00, 4.00),
            'high' => $this->randomInRange(4.50, 8.00),
            default => $this->randomInRange(1.50, 3.00),
        };

        return [
            'suggested'       => $this->clamp($suggested),
            'confidence'      => $this->clampConf(0.4 + $bestConfidence * 0.4),
            'matched_pattern' => $bestMatch,
            'predicted_cat'   => $predictedCat,
        ];
    }

    // ================================================================
    //  MODULE 5 : CHAÎNE DE MARKOV (Transitions)
    // ================================================================

    private function analyzeMarkov(): array
    {
        $h = $this->history;
        $cats = $this->categorize($h);
        $n = count($cats);

        if ($n < 10) {
            return ['suggested' => $this->clamp(2.0), 'confidence' => 0.3, 'transition' => null];
        }

        // Construire la matrice de transition (1er ordre)
        $transitions = ['low' => ['low' => 0, 'mid' => 0, 'high' => 0], 'mid' => ['low' => 0, 'mid' => 0, 'high' => 0], 'high' => ['low' => 0, 'mid' => 0, 'high' => 0]];

        for ($i = 0; $i < $n - 1; $i++) {
            $from = $cats[$i];
            $to   = $cats[$i + 1];
            $transitions[$from][$to]++;
        }

        // Normaliser (Laplace smoothing)
        foreach ($transitions as $from => &$toCounts) {
            $total = array_sum($toCounts) + 3; // +3 pour le smoothing
            foreach ($toCounts as $to => &$count) {
                $count = ($count + 1) / $total; // +1 pour chaque catégorie
            }
        }

        // Matrice de transition d'ordre 2 (bigrammes)
        $transitions2 = [];
        for ($i = 0; $i < $n - 2; $i++) {
            $key = $cats[$i] . ',' . $cats[$i + 1];
            $next = $cats[$i + 2];
            if (!isset($transitions2[$key])) {
                $transitions2[$key] = ['low' => 0, 'mid' => 0, 'high' => 0];
            }
            $transitions2[$key][$next]++;
        }

        // Normaliser ordre 2
        foreach ($transitions2 as $key => &$counts) {
            $total = array_sum($counts) + 3;
            foreach ($counts as $cat => &$count) {
                $count = ($count + 1) / $total;
            }
        }

        // Prédire avec ordre 2 si possible, sinon ordre 1
        $currentState = $cats[$n - 1];
        $probs = $transitions[$currentState];

        if ($n >= 2) {
            $bigramKey = $cats[$n - 2] . ',' . $cats[$n - 1];
            if (isset($transitions2[$bigramKey])) {
                // Combiner ordre 1 et ordre 2 (pondération 40%/60%)
                $bigramProbs = $transitions2[$bigramKey];
                $combined = [];
                foreach (['low', 'mid', 'high'] as $cat) {
                    $combined[$cat] = ($probs[$cat] * 0.4) + ($bigramProbs[$cat] * 0.6);
                }
                $probs = $combined;
            }
        }

        // Trouver la catégorie la plus probable
        arsort($probs);
        $predictedCat = array_key_first($probs);
        $probValue = $probs[$predictedCat];

        $suggested = match ($predictedCat) {
            'low'  => $this->randomInRange(1.15, 1.85),
            'mid'  => $this->randomInRange(1.90, 3.80),
            'high' => $this->randomInRange(4.00, 7.50),
            default => $this->randomInRange(1.50, 3.00),
        };

        // La confiance dépend de la probabilité de la catégorie prédite
        $confidence = 0.35 + ($probValue * 0.45);

        return [
            'suggested'    => $this->clamp($suggested),
            'confidence'   => $this->clampConf($confidence),
            'transition'   => "{$currentState} -> {$predictedCat} (" . round($probValue * 100, 1) . "%)",
            'current_state'=> $currentState,
            'probabilities'=> array_map(fn($v) => round($v, 3), $probs),
        ];
    }

    // ================================================================
    //  MODULE 6 : RÉVERSION À LA MOYENNE
    // ================================================================

    private function analyzeMeanReversion(): array
    {
        $h = $this->history;
        $n = count($h);

        // Moyennes mobiles
        $shortMean = $this->avg(array_slice($h, -5));
        $mediumMean = $n >= 10 ? $this->avg(array_slice($h, -10)) : $shortMean;
        $longMean = $n >= 20 ? $this->avg(array_slice($h, -20)) : $mediumMean;

        $last = $h[$n - 1];

        // Force de réversion (distance normalisée)
        $shortForce = ($shortMean - $last) / max($shortMean, 0.01);
        $mediumForce = ($mediumMean - $last) / max($mediumMean, 0.01);
        $longForce = ($longMean - $last) / max($longMean, 0.01);

        // Score de réversion combiné (pondération dégressive)
        $reversionScore = ($shortForce * 0.50) + ($mediumForce * 0.30) + ($longForce * 0.20);

        // Le prédit doit tendre vers la moyenne
        $target = $shortMean + ($mediumMean - $shortMean) * 0.3;

        // Ajuster selon la force de réversion
        $adjustment = $reversionScore * 0.4;
        $suggested = $last + ($adjustment * abs($last - $target));

        // Confiance basée sur la distance à la moyenne
        $distance = abs($last - $shortMean) / max($shortMean, 0.01);
        $confidence = min(0.4 + $distance * 0.3, 0.85);

        // Direction
        $direction = $reversionScore > 0.05 ? 'up' : ($reversionScore < -0.05 ? 'down' : 'neutral');

        return [
            'suggested'     => $this->clamp($suggested),
            'confidence'    => $this->clampConf($confidence),
            'direction'     => $direction,
            'reversion_score' => round($reversionScore, 4),
            'short_mean'    => round($shortMean, 3),
            'medium_mean'   => round($mediumMean, 3),
            'long_mean'     => round($longMean, 3),
            'distance'      => round($distance, 4),
        ];
    }

    // ================================================================
    //  MODULE 7 : DÉTECTION DE CYCLES
    // ================================================================

    private function analyzeCycles(): array
    {
        $h = $this->history;
        $cats = $this->categorize($h);
        $n = count($cats);

        if ($n < 15) {
            return ['suggested' => $this->clamp(2.0), 'confidence' => 0.25, 'cycle_detected' => false, 'cycle_length' => 0];
        }

        $bestCycle = 0;
        $bestScore = 0;
        $nextInCycle = null;

        // Tester des longueurs de cycle de 3 à 10
        foreach (range(3, min(10, (int)($n / 2))) as $cycleLen) {
            // Auto-corrélation pour cette longueur
            $matches = 0;
            $total = 0;

            for ($i = $cycleLen; $i < $n; $i++) {
                $current = $cats[$i];
                $past = $cats[$i - $cycleLen];
                $total++;
                if ($current === $past) {
                    $matches++;
                }
            }

            if ($total > 0) {
                $score = $matches / $total;
                if ($score > $bestScore && $score > 0.35) {
                    $bestScore = $score;
                    $bestCycle = $cycleLen;
                }
            }
        }

        // Si un cycle est détecté, prédire la prochaine valeur
        $cycleDetected = $bestCycle > 0 && $bestScore > 0.35;

        if ($cycleDetected) {
            // Position actuelle dans le cycle
            $pos = $n % $bestCycle;
            // La valeur correspondante dans le cycle précédent
            $prevCycleStart = max(0, $n - $bestCycle);
            if ($prevCycleStart + $pos < $n) {
                $nextInCycle = $h[$prevCycleStart + $pos];
            }

            $suggested = $nextInCycle ? $nextInCycle + $this->randomInRange(-0.3, 0.3) : $this->avg($h);
            $confidence = 0.4 + ($bestScore * 0.35);
        } else {
            $suggested = $this->avg($h);
            $confidence = 0.3;
        }

        return [
            'suggested'      => $this->clamp($suggested),
            'confidence'     => $this->clampConf($confidence),
            'cycle_detected' => $cycleDetected,
            'cycle_length'   => $bestCycle,
            'cycle_score'    => round($bestScore, 3),
        ];
    }

    // ================================================================
    //  MODULE 8 : SCORE DE MOMENTUM
    // ================================================================

    private function analyzeMomentum(): array
    {
        $h = $this->history;
        $n = count($h);

        if ($n < 6) {
            return ['suggested' => $this->clamp(2.0), 'confidence' => 0.3, 'momentum' => 'neutral'];
        }

        // Calculer les taux de changement (dérivée discrète)
        $changes = [];
        for ($i = 1; $i < $n; $i++) {
            $changes[] = $h[$i] - $h[$i - 1];
        }

        // Momentum court terme (3 derniers changements)
        $shortMom = array_sum(array_slice($changes, -3)) / min(3, count($changes));
        // Momentum moyen terme (6 derniers changements)
        $medMom = count($changes) >= 6 ? array_sum(array_slice($changes, -6)) / 6 : $shortMom;

        // Accélération (changement du momentum)
        $acceleration = $shortMom - $medMom;

        // Déterminer la direction du momentum
        $momentum = 'neutral';
        if ($shortMom > 0.3) {
            $momentum = 'strong_up';
        } elseif ($shortMom > 0.1) {
            $momentum = 'up';
        } elseif ($shortMom < -0.3) {
            $momentum = 'strong_down';
        } elseif ($shortMom < -0.1) {
            $momentum = 'down';
        }

        // Prédiction basée sur le momentum et l'accélération
        $last = $h[$n - 1];

        if ($momentum === 'strong_up' && $acceleration > 0) {
            // Accélération haussière → prédire plus haut
            $suggested = $last + $shortMom * 0.6;
            $confidence = 0.55;
        } elseif ($momentum === 'strong_down' && $acceleration < 0) {
            // Accélération baissière → prédire plus bas
            $suggested = $last + $shortMom * 0.4;
            $confidence = 0.55;
        } elseif ($acceleration < -0.2 && $momentum === 'up') {
            // Décélération (montée qui ralentit) → prédire stabilité
            $suggested = $last * 0.95;
            $confidence = 0.50;
        } elseif ($acceleration > 0.2 && $momentum === 'down') {
            // Ralentissement de la baisse → rebond possible
            $suggested = $last + abs($shortMom) * 0.3;
            $confidence = 0.50;
        } else {
            // Momentum neutre ou faible → prédire modéré
            $suggested = $this->avg(array_slice($h, -5));
            $confidence = 0.40;
        }

        return [
            'suggested'    => $this->clamp($suggested),
            'confidence'   => $this->clampConf($confidence),
            'momentum'     => $momentum,
            'acceleration' => round($acceleration, 4),
            'short_mom'    => round($shortMom, 3),
            'med_mom'      => round($medMom, 3),
        ];
    }

    // ================================================================
    //  COMBINAISON ET PONDÉRATION ADAPTATIVE
    // ================================================================

    /**
     * Ajuste les poids en fonction de la qualité des données disponibles.
     */
    private function adaptWeights(): array
    {
        $n = count($this->history);
        $w = $this->weights;

        // Si peu de données, réduire le poids des modules complexes
        if ($n < 15) {
            $w['pattern'] *= 0.5;
            $w['markov'] *= 0.7;
            $w['cycle'] *= 0.3;
            $w['streak'] *= 1.3;
            $w['meanReversion'] *= 1.2;
        }

        // Si beaucoup de données, augmenter le poids des modules avancés
        if ($n >= 30) {
            $w['pattern'] *= 1.2;
            $w['markov'] *= 1.15;
            $w['cycle'] *= 1.1;
        }

        // Normaliser pour que la somme = 1.0
        $sum = array_sum($w);
        foreach ($w as $k => &$v) {
            $v = $v / $sum;
        }

        return $w;
    }

    /**
     * Combine les résultats de tous les modules avec leurs poids.
     */
    private function combineModules(array $weights): array
    {
        $weightedSum = 0;
        $confidenceSum = 0;
        $directionVotes = ['up' => 0, 'down' => 0, 'neutral' => 0];
        $totalWeight = 0;

        foreach ($this->moduleResults as $name => $result) {
            $weight = $weights[$name] ?? 0;
            $totalWeight += $weight;

            // Moyenne pondérée des suggestions
            $weightedSum += $result['suggested'] * $weight;
            $confidenceSum += $result['confidence'] * $weight;

            // Votes de direction
            $dir = $result['direction'] ?? ($result['momentum'] ?? 'neutral');
            if ($dir === 1 || $dir === 'up' || $dir === 'strong_up') {
                $directionVotes['up'] += $weight;
            } elseif ($dir === -1 || $dir === 'down' || $dir === 'strong_down') {
                $directionVotes['down'] += $weight;
            } else {
                $directionVotes['neutral'] += $weight;
            }
        }

        // Direction majoritaire
        asort($directionVotes);
        $direction = array_key_last($directionVotes);

        return [
            'weighted_suggestion' => $totalWeight > 0 ? $weightedSum / $totalWeight : 2.0,
            'confidence'          => $totalWeight > 0 ? $confidenceSum / $totalWeight : 0.5,
            'direction'           => $direction,
            'direction_votes'     => $directionVotes,
        ];
    }

    /**
     * Génère la prédiction finale avec légère variance contrôlée.
     */
    private function generateFinalPrediction(array $combined): float
    {
        $base = $combined['weighted_suggestion'];
        $conf = $combined['confidence'];

        // Ajouter une légère variance contrôlée (±5%) pour un rendu naturel
        $variance = $base * 0.05;
        $noise = $this->randomInRange(-$variance, $variance);

        // Plus la confiance est haute, moins de bruit
        $noise *= (1 - $conf * 0.5);

        $final = $base + $noise;

        return $this->clamp($final);
    }

    /**
     * Collecte les signaux clés détectés par les modules.
     */
    private function collectSignals(): array
    {
        $signals = [];

        if (!empty($this->moduleResults['streak']['signal'])) {
            $signals[] = $this->moduleResults['streak']['signal'];
        }

        if (!empty($this->moduleResults['pattern']['matched_pattern'])) {
            $signals[] = 'pattern_matched';
        }

        if (!empty($this->moduleResults['cycle']['cycle_detected'])) {
            $signals[] = 'cycle_' . $this->moduleResults['cycle']['cycle_length'];
        }

        $mom = $this->moduleResults['momentum']['momentum'] ?? '';
        if (in_array($mom, ['strong_up', 'strong_down'])) {
            $signals[] = $mom . '_momentum';
        }

        $vol = $this->moduleResults['volatility']['regime'] ?? '';
        if ($vol === 'high') {
            $signals[] = 'high_volatility';
        } elseif ($vol === 'low') {
            $signals[] = 'low_volatility';
        }

        return $signals;
    }

    /**
     * Résumé des résultats de chaque module pour le debug/admin.
     */
    private function summarizeModules(): array
    {
        $summary = [];
        foreach ($this->moduleResults as $name => $result) {
            $summary[$name] = [
                'suggested'  => round($result['suggested'], 2),
                'confidence' => round($result['confidence'], 2),
            ];
        }
        return $summary;
    }

    // ================================================================
    //  FALLBACK — Quand il n'y a pas assez de données
    // ================================================================

    private function fallbackPrediction(): array
    {
        $n = count($this->history);
        $avg = $n > 0 ? $this->avg($this->history) : 2.0;

        return [
            'prediction' => round($this->clamp($avg + $this->randomInRange(-0.3, 0.3)), 2),
            'confidence' => 0.25,
            'modules'    => [],
            'signals'    => ['insufficient_data'],
            'analysis'   => [
                'rounds_analyzed' => $n,
                'avg'             => round($avg, 2),
                'direction'       => 'neutral',
            ],
        ];
    }

    // ================================================================
    //  UTILITAIRES MATHÉMATIQUES
    // ================================================================

    /** Moyenne arithmétique */
    private function avg(array $values): float
    {
        if (empty($values)) return 0.0;
        return array_sum($values) / count($values);
    }

    /** Écart-type */
    private function stdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0.0;
        $mean = $this->avg($values);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / ($n - 1);
        return sqrt($variance);
    }

    /** Moyenne Mobile Simple */
    private function sma(array $values, int $period): float
    {
        $slice = array_slice($values, -$period);
        return count($slice) > 0 ? array_sum($slice) / count($slice) : 0.0;
    }

    /** Moyenne Mobile Exponentielle */
    private function ema(array $values, int $period): float
    {
        $n = count($values);
        if ($n === 0) return 0.0;

        $k = 2 / ($period + 1);
        $ema = $values[0];

        for ($i = 1; $i < $n; $i++) {
            $ema = ($values[$i] * $k) + ($ema * (1 - $k));
        }

        return $ema;
    }

    /** Catégoriser un multiplicateur : low / mid / high */
    private function categorize(array $values): array
    {
        return array_map(function ($v) {
            if ($v < 2.0) return 'low';
            if ($v < 5.0) return 'mid';
            return 'high';
        }, $values);
    }

    /** Limiter une valeur entre minPred et maxPred */
    private function clamp(float $value): float
    {
        return max($this->minPred, min($this->maxPred, $value));
    }

    /** Limiter la confiance entre 0.10 et 0.95 */
    private function clampConf(float $value): float
    {
        return max(0.10, min(0.95, $value));
    }

    /** Nombre aléatoire dans une plage */
    private function randomInRange(float $min, float $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }
}
