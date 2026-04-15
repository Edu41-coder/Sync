<?php
/**
 * ====================================================================
 * SYND_GEST - Autoloader PSR-4 simplifié
 * ====================================================================
 * Charge automatiquement les classes depuis les dossiers :
 *   - app/core/        → Classes système (Controller, Model, Security, etc.)
 *   - app/models/      → Modèles (User, Lot, Residence, etc.)
 *   - app/controllers/ → Contrôleurs (AdminController, HomeController, etc.)
 *
 * Pas de namespaces — résolution par nom de classe + convention de nommage.
 */

class Autoloader {

    /** @var array Dossiers où chercher les classes (ordre de priorité) */
    private static array $directories = [];

    /** @var array Cache des classes déjà résolues [className => filePath] */
    private static array $cache = [];

    /**
     * Enregistrer l'autoloader
     * @param string $basePath Chemin absolu vers la racine du projet (ex: /var/www/Synd_Gest)
     */
    public static function register(string $basePath): void {
        // Normaliser le chemin
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');

        self::$directories = [
            $basePath . '/app/core',
            $basePath . '/app/models',
            $basePath . '/app/controllers',
        ];

        spl_autoload_register([self::class, 'loadClass']);
    }

    /**
     * Tenter de charger une classe
     * @param string $className Nom de la classe (sans namespace)
     */
    public static function loadClass(string $className): void {
        // Vérifier le cache
        if (isset(self::$cache[$className])) {
            require_once self::$cache[$className];
            return;
        }

        // Chercher dans chaque dossier
        foreach (self::$directories as $dir) {
            $file = $dir . '/' . $className . '.php';
            if (file_exists($file)) {
                self::$cache[$className] = $file;
                require_once $file;
                return;
            }
        }
    }
}
