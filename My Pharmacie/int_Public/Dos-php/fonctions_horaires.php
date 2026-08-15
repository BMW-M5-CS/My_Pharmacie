<?php

// ===================================================================
// Calcul du statut réel d'une pharmacie à l'instant présent.
//
// Avant cette fonction, le badge "Ouverte" était affiché uniquement sur la
// base de statut_garde = false, SANS AUCUNE comparaison avec l'heure actuelle
// ni avec heure_ouverture/heure_fermeture. Résultat : une pharmacie fermée
// depuis des heures s'affichait quand même "Ouverte" en vert.
// ===================================================================

// Détermine si l'heure actuelle tombe dans le créneau [heure_ouverture, heure_fermeture].
// Gère le cas où le créneau traverse minuit (ex: 20:00 -> 06:00).
function estDansCreneauHoraire(string $heure_ouverture, string $heure_fermeture, ?string $heure_reference = null): bool {

    $reference = $heure_reference ?? date('H:i:s');

    // Normalise au format HH:MM:SS pour une comparaison de chaînes fiable
    $ouverture  = substr($heure_ouverture, 0, 8);
    $fermeture  = substr($heure_fermeture, 0, 8);
    $actuelle   = substr($reference, 0, 8);

    if ($ouverture === $fermeture) {
        // Ouverture == fermeture : convention "ouvert 24h/24"
        return true;
    }

    if ($ouverture < $fermeture) {
        // Créneau classique dans la même journée (ex: 08:00 -> 19:00)
        return $actuelle >= $ouverture && $actuelle < $fermeture;
    }

    // Créneau qui traverse minuit (ex: 20:00 -> 06:00)
    return $actuelle >= $ouverture || $actuelle < $fermeture;
}

// Calcule le statut final affiché : 'garde', 'ouverte', ou 'fermee'.
// Une pharmacie "de garde" (statut_garde = true en base) est mise en avant
// comme telle en priorité, qu'elle soit ou non dans son créneau horaire normal
// (le principe même d'une pharmacie de garde est d'assurer une continuité
// même hors des horaires classiques).
function calculerStatutPharmacie(bool $statut_garde, string $heure_ouverture, string $heure_fermeture, ?string $heure_reference = null): string {

    if ($statut_garde) {
        return 'garde';
    }

    return estDansCreneauHoraire($heure_ouverture, $heure_fermeture, $heure_reference) ? 'ouverte' : 'fermee';
}