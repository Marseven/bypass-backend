<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Sensor;
use App\Models\Site;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $moanda = Site::where('code', 'CML-MOANDA')->first();
        $owendo = Site::where('code', 'CML-OWENDO')->first();

        // ── 10 Zones ─────────────────────────────────────────────

        $zoneExtraction = Zone::create([
            'site_id' => $moanda->id,
            'name' => 'Plateau Bangombe - Extraction',
            'description' => 'Zone d\'extraction a ciel ouvert du manganese',
            'status' => true,
        ]);

        $zoneUtm = Zone::create([
            'site_id' => $moanda->id,
            'name' => 'Usine de Traitement du Minerai (UTM)',
            'description' => 'Broyage, separation magnetique, frittage',
            'status' => true,
        ]);

        $zoneConcassage = Zone::create([
            'site_id' => $moanda->id,
            'name' => 'Station de Concassage',
            'description' => 'Concassage primaire et secondaire du minerai',
            'status' => true,
        ]);

        $zoneCentrale = Zone::create([
            'site_id' => $moanda->id,
            'name' => 'Centrale Electrique',
            'description' => 'Production et distribution d\'energie',
            'status' => true,
        ]);

        $zonePompage = Zone::create([
            'site_id' => $moanda->id,
            'name' => 'Station de Pompage',
            'description' => 'Pompage et traitement des eaux industrielles',
            'status' => true,
        ]);

        $zoneAteliers = Zone::create([
            'site_id' => $moanda->id,
            'name' => 'Ateliers de Maintenance',
            'description' => 'Maintenance lourde des engins et equipements',
            'status' => true,
        ]);

        $zoneResidus = Zone::create([
            'site_id' => $moanda->id,
            'name' => 'Parc a Residus Miniers',
            'description' => 'Gestion des steriles et residus de traitement',
            'status' => true,
        ]);

        $zoneQuai = Zone::create([
            'site_id' => $owendo->id,
            'name' => 'Quai de Chargement Maritime',
            'description' => 'Chargement du minerai sur les navires',
            'status' => true,
        ]);

        $zoneStockage = Zone::create([
            'site_id' => $owendo->id,
            'name' => 'Parc de Stockage Minerai',
            'description' => 'Stockage et homogeneisation du minerai',
            'status' => true,
        ]);

        $zoneCriblage = Zone::create([
            'site_id' => $owendo->id,
            'name' => 'Station de Criblage Owendo',
            'description' => 'Criblage et calibrage avant expedition',
            'status' => true,
        ]);

        // ── 24 Equipements + ~50 Capteurs ────────────────────────

        // --- Extraction (3 equipements) ---
        $this->createEquipmentWithSensors($zoneExtraction, [
            [
                'code' => 'EXT-001',
                'name' => 'Excavatrice Hydraulique CAT 6040',
                'type' => 'Excavatrice',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'Caterpillar',
                'niveau_sil' => 'na',
                'description' => 'Excavatrice principale pour extraction minerai de manganese',
                'sensors' => [
                    ['code' => 'CAP-EXT-001-01', 'name' => 'Temperature huile hydraulique', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '95', 'last_reading' => 62.5],
                    ['code' => 'CAP-EXT-001-02', 'name' => 'Pression circuit hydraulique', 'type' => 'pressure', 'unite' => 'bar', 'seuil_critique' => '350', 'last_reading' => 245.0],
                ],
            ],
            [
                'code' => 'EXT-002',
                'name' => 'Dumper Komatsu HD785-7',
                'type' => 'Tombereau',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'Komatsu',
                'niveau_sil' => 'na',
                'description' => 'Transport du minerai brut vers la station de concassage',
                'sensors' => [
                    ['code' => 'CAP-EXT-002-01', 'name' => 'Temperature moteur diesel', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '110', 'last_reading' => 88.3],
                    ['code' => 'CAP-EXT-002-02', 'name' => 'Pression pneus', 'type' => 'pressure', 'unite' => 'bar', 'seuil_critique' => '7.5', 'last_reading' => 6.2],
                ],
            ],
            [
                'code' => 'EXT-003',
                'name' => 'Foreuse Atlas Copco DM45',
                'type' => 'Foreuse',
                'type_systeme' => 'process',
                'criticite' => 'moyenne',
                'fabricant' => 'Atlas Copco',
                'niveau_sil' => 'na',
                'description' => 'Forage des trous de mine pour abattage a l\'explosif',
                'sensors' => [
                    ['code' => 'CAP-EXT-003-01', 'name' => 'Vibration tete de forage', 'type' => 'vibration', 'unite' => 'mm/s', 'seuil_critique' => '25', 'last_reading' => 12.8],
                    ['code' => 'CAP-EXT-003-02', 'name' => 'Pression air comprime', 'type' => 'pressure', 'unite' => 'bar', 'seuil_critique' => '12', 'last_reading' => 8.5],
                ],
            ],
        ]);

        // --- UTM (4 equipements) ---
        $this->createEquipmentWithSensors($zoneUtm, [
            [
                'code' => 'UTM-001',
                'name' => 'Broyeur a Boulets SAG Mill',
                'type' => 'Broyeur',
                'type_systeme' => 'process',
                'criticite' => 'critique',
                'fabricant' => 'FLSmidth',
                'niveau_sil' => 'sil1',
                'fonction_securite' => 'Arret d\'urgence sur surcharge broyeur',
                'description' => 'Broyage primaire du minerai de manganese',
                'sensors' => [
                    ['code' => 'CAP-UTM-001-01', 'name' => 'Vibration palier broyeur', 'type' => 'vibration', 'unite' => 'mm/s', 'seuil_critique' => '12', 'last_reading' => 4.7],
                    ['code' => 'CAP-UTM-001-02', 'name' => 'Temperature palier broyeur', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '85', 'last_reading' => 58.2],
                    ['code' => 'CAP-UTM-001-03', 'name' => 'Puissance moteur broyeur', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '95', 'last_reading' => 72.0],
                ],
            ],
            [
                'code' => 'UTM-002',
                'name' => 'Separateur Magnetique Haute Intensite',
                'type' => 'Separateur',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'Metso Outotec',
                'niveau_sil' => 'sil1',
                'fonction_securite' => 'Protection surchauffe bobines',
                'description' => 'Separation magnetique du manganese et des gangues',
                'sensors' => [
                    ['code' => 'CAP-UTM-002-01', 'name' => 'Temperature bobine magnetique', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '120', 'last_reading' => 78.5],
                    ['code' => 'CAP-UTM-002-02', 'name' => 'Courant bobine', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '100', 'last_reading' => 68.0],
                ],
            ],
            [
                'code' => 'UTM-003',
                'name' => 'Four de Frittage Rotatif',
                'type' => 'Four',
                'type_systeme' => 'securite',
                'criticite' => 'critique',
                'fabricant' => 'Outotec',
                'niveau_sil' => 'sil2',
                'fonction_securite' => 'Detection flamme et arret gaz d\'urgence',
                'description' => 'Frittage du minerai de manganese a haute temperature',
                'sensors' => [
                    ['code' => 'CAP-UTM-003-01', 'name' => 'Temperature zone de frittage', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '1250', 'last_reading' => 1080.0],
                    ['code' => 'CAP-UTM-003-02', 'name' => 'Pression gaz combustible', 'type' => 'pressure', 'unite' => 'kPa', 'seuil_critique' => '50', 'last_reading' => 32.5],
                    ['code' => 'CAP-UTM-003-03', 'name' => 'Detection flamme bruleur', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '10', 'last_reading' => 98.0],
                ],
            ],
            [
                'code' => 'UTM-004',
                'name' => 'Filtre-Presse Larox PF30',
                'type' => 'Filtre',
                'type_systeme' => 'process',
                'criticite' => 'moyenne',
                'fabricant' => 'Larox',
                'niveau_sil' => 'na',
                'description' => 'Filtration et deshydratation du concentre de manganese',
                'sensors' => [
                    ['code' => 'CAP-UTM-004-01', 'name' => 'Pression filtration', 'type' => 'pressure', 'unite' => 'bar', 'seuil_critique' => '16', 'last_reading' => 10.2],
                    ['code' => 'CAP-UTM-004-02', 'name' => 'Niveau bac alimentation', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '95', 'last_reading' => 65.0],
                ],
            ],
        ]);

        // --- Concassage (3 equipements) ---
        $this->createEquipmentWithSensors($zoneConcassage, [
            [
                'code' => 'CON-001',
                'name' => 'Concasseur Primaire a Machoires',
                'type' => 'Concasseur',
                'type_systeme' => 'process',
                'criticite' => 'critique',
                'fabricant' => 'Sandvik',
                'niveau_sil' => 'sil1',
                'fonction_securite' => 'Protection surcharge concasseur',
                'description' => 'Reduction granulometrique primaire du minerai brut',
                'sensors' => [
                    ['code' => 'CAP-CON-001-01', 'name' => 'Vibration chassis concasseur', 'type' => 'vibration', 'unite' => 'mm/s', 'seuil_critique' => '15', 'last_reading' => 8.3],
                    ['code' => 'CAP-CON-001-02', 'name' => 'Temperature palier excentrique', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '80', 'last_reading' => 52.1],
                ],
            ],
            [
                'code' => 'CON-002',
                'name' => 'Convoyeur a Bande Principal',
                'type' => 'Convoyeur',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'Continental',
                'niveau_sil' => 'sil1',
                'fonction_securite' => 'Arret d\'urgence sur derapage bande',
                'description' => 'Transport du minerai concasse vers l\'UTM',
                'sensors' => [
                    ['code' => 'CAP-CON-002-01', 'name' => 'Vitesse bande convoyeur', 'type' => 'flow', 'unite' => 'm/s', 'seuil_critique' => '0.5', 'last_reading' => 2.8],
                    ['code' => 'CAP-CON-002-02', 'name' => 'Alignement bande', 'type' => 'level', 'unite' => 'mm', 'seuil_critique' => '50', 'last_reading' => 8.0],
                ],
            ],
            [
                'code' => 'CON-003',
                'name' => 'Crible Vibrant Primaire',
                'type' => 'Crible',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'Metso',
                'niveau_sil' => 'na',
                'description' => 'Tamisage et classification granulometrique du minerai',
                'sensors' => [
                    ['code' => 'CAP-CON-003-01', 'name' => 'Vibration crible', 'type' => 'vibration', 'unite' => 'mm/s', 'seuil_critique' => '20', 'last_reading' => 11.5],
                    ['code' => 'CAP-CON-003-02', 'name' => 'Temperature palier vibreur', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '75', 'last_reading' => 48.0],
                ],
            ],
        ]);

        // --- Centrale Electrique (2 equipements) ---
        $this->createEquipmentWithSensors($zoneCentrale, [
            [
                'code' => 'NRG-001',
                'name' => 'Groupe Electrogene Diesel 5MW',
                'type' => 'Groupe electrogene',
                'type_systeme' => 'securite',
                'criticite' => 'critique',
                'fabricant' => 'Caterpillar Energy',
                'niveau_sil' => 'sil2',
                'fonction_securite' => 'Arret d\'urgence sur survitesse et surchauffe',
                'description' => 'Alimentation electrique principale du complexe minier',
                'sensors' => [
                    ['code' => 'CAP-NRG-001-01', 'name' => 'Temperature eau de refroidissement', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '95', 'last_reading' => 72.0],
                    ['code' => 'CAP-NRG-001-02', 'name' => 'Pression huile lubrification', 'type' => 'pressure', 'unite' => 'bar', 'seuil_critique' => '2', 'last_reading' => 4.8],
                    ['code' => 'CAP-NRG-001-03', 'name' => 'Vibration moteur diesel', 'type' => 'vibration', 'unite' => 'mm/s', 'seuil_critique' => '10', 'last_reading' => 3.2],
                ],
            ],
            [
                'code' => 'NRG-002',
                'name' => 'Transformateur HT/BT 20kV',
                'type' => 'Transformateur',
                'type_systeme' => 'securite',
                'criticite' => 'critique',
                'fabricant' => 'Schneider Electric',
                'niveau_sil' => 'sil2',
                'fonction_securite' => 'Protection Buchholz et detection surchauffe',
                'description' => 'Transformation haute tension vers basse tension pour distribution',
                'sensors' => [
                    ['code' => 'CAP-NRG-002-01', 'name' => 'Temperature huile transformateur', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '85', 'last_reading' => 55.0],
                    ['code' => 'CAP-NRG-002-02', 'name' => 'Niveau huile isolante', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '20', 'last_reading' => 92.0],
                ],
            ],
        ]);

        // --- Station de Pompage (2 equipements) ---
        $this->createEquipmentWithSensors($zonePompage, [
            [
                'code' => 'PMP-001',
                'name' => 'Pompe Centrifuge DN300',
                'type' => 'Pompe',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'KSB',
                'niveau_sil' => 'sil1',
                'fonction_securite' => 'Protection marche a sec et surchauffe',
                'description' => 'Pompage eau de process vers l\'usine de traitement',
                'sensors' => [
                    ['code' => 'CAP-PMP-001-01', 'name' => 'Debit pompe', 'type' => 'flow', 'unite' => 'm3/h', 'seuil_critique' => '50', 'last_reading' => 180.0],
                    ['code' => 'CAP-PMP-001-02', 'name' => 'Pression refoulement', 'type' => 'pressure', 'unite' => 'bar', 'seuil_critique' => '12', 'last_reading' => 7.5],
                    ['code' => 'CAP-PMP-001-03', 'name' => 'Vibration pompe', 'type' => 'vibration', 'unite' => 'mm/s', 'seuil_critique' => '8', 'last_reading' => 2.9],
                ],
            ],
            [
                'code' => 'PMP-002',
                'name' => 'Station de Traitement des Eaux',
                'type' => 'Station de traitement',
                'type_systeme' => 'securite',
                'criticite' => 'critique',
                'fabricant' => 'Veolia',
                'niveau_sil' => 'sil2',
                'fonction_securite' => 'Detection qualite eau et arret rejet',
                'description' => 'Traitement des eaux industrielles avant rejet ou recyclage',
                'sensors' => [
                    ['code' => 'CAP-PMP-002-01', 'name' => 'pH eau traitee', 'type' => 'level', 'unite' => 'pH', 'seuil_critique' => '9.5', 'last_reading' => 7.2],
                    ['code' => 'CAP-PMP-002-02', 'name' => 'Turbidite eau traitee', 'type' => 'level', 'unite' => 'NTU', 'seuil_critique' => '50', 'last_reading' => 12.0],
                ],
            ],
        ]);

        // --- Ateliers de Maintenance (2 equipements) ---
        $this->createEquipmentWithSensors($zoneAteliers, [
            [
                'code' => 'MNT-001',
                'name' => 'Pont Roulant 50T',
                'type' => 'Pont roulant',
                'type_systeme' => 'securite',
                'criticite' => 'haute',
                'fabricant' => 'Konecranes',
                'niveau_sil' => 'sil2',
                'fonction_securite' => 'Limiteur de charge et arret d\'urgence',
                'description' => 'Levage et manutention de pieces lourdes en atelier',
                'sensors' => [
                    ['code' => 'CAP-MNT-001-01', 'name' => 'Charge pont roulant', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '100', 'last_reading' => 45.0],
                    ['code' => 'CAP-MNT-001-02', 'name' => 'Temperature moteur de levage', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '80', 'last_reading' => 42.5],
                ],
            ],
            [
                'code' => 'MNT-002',
                'name' => 'Compresseur Air Atlas Copco GA90',
                'type' => 'Compresseur',
                'type_systeme' => 'process',
                'criticite' => 'moyenne',
                'fabricant' => 'Atlas Copco',
                'niveau_sil' => 'na',
                'description' => 'Production d\'air comprime pour outillage pneumatique',
                'sensors' => [
                    ['code' => 'CAP-MNT-002-01', 'name' => 'Pression air sortie', 'type' => 'pressure', 'unite' => 'bar', 'seuil_critique' => '10', 'last_reading' => 7.2],
                    ['code' => 'CAP-MNT-002-02', 'name' => 'Temperature air comprime', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '110', 'last_reading' => 65.0],
                ],
            ],
        ]);

        // --- Parc a Residus (2 equipements) ---
        $this->createEquipmentWithSensors($zoneResidus, [
            [
                'code' => 'RES-001',
                'name' => 'Systeme Detection Fuites Digue',
                'type' => 'Systeme de detection',
                'type_systeme' => 'securite',
                'criticite' => 'critique',
                'fabricant' => 'Siemens',
                'niveau_sil' => 'sil2',
                'fonction_securite' => 'Alarme fuite digue de retention des residus',
                'description' => 'Surveillance integrite de la digue de retention des residus miniers',
                'sensors' => [
                    ['code' => 'CAP-RES-001-01', 'name' => 'Niveau piezometrique digue', 'type' => 'level', 'unite' => 'm', 'seuil_critique' => '12', 'last_reading' => 8.5],
                    ['code' => 'CAP-RES-001-02', 'name' => 'Debit drain de pied', 'type' => 'flow', 'unite' => 'l/min', 'seuil_critique' => '50', 'last_reading' => 12.0],
                ],
            ],
            [
                'code' => 'RES-002',
                'name' => 'Pompe de Recirculation Boues',
                'type' => 'Pompe',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'Flygt',
                'niveau_sil' => 'sil1',
                'fonction_securite' => 'Protection marche a sec',
                'description' => 'Recirculation des boues de traitement vers le decanteur',
                'sensors' => [
                    ['code' => 'CAP-RES-002-01', 'name' => 'Niveau bac de boues', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '10', 'last_reading' => 62.0],
                    ['code' => 'CAP-RES-002-02', 'name' => 'Debit recirculation', 'type' => 'flow', 'unite' => 'm3/h', 'seuil_critique' => '20', 'last_reading' => 85.0],
                ],
            ],
        ]);

        // --- Quai de Chargement Maritime (2 equipements) ---
        $this->createEquipmentWithSensors($zoneQuai, [
            [
                'code' => 'QCH-001',
                'name' => 'Portique de Chargement Maritime',
                'type' => 'Portique',
                'type_systeme' => 'process',
                'criticite' => 'critique',
                'fabricant' => 'Liebherr',
                'niveau_sil' => 'sil1',
                'fonction_securite' => 'Arret d\'urgence vent fort et surcharge',
                'description' => 'Chargement du minerai de manganese dans les navires vraquiers',
                'sensors' => [
                    ['code' => 'CAP-QCH-001-01', 'name' => 'Charge portique', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '100', 'last_reading' => 55.0],
                    ['code' => 'CAP-QCH-001-02', 'name' => 'Vitesse vent anemometre', 'type' => 'flow', 'unite' => 'km/h', 'seuil_critique' => '72', 'last_reading' => 18.0],
                    ['code' => 'CAP-QCH-001-03', 'name' => 'Vibration fleche portique', 'type' => 'vibration', 'unite' => 'mm/s', 'seuil_critique' => '8', 'last_reading' => 2.1],
                ],
            ],
            [
                'code' => 'QCH-002',
                'name' => 'Convoyeur Maritime Telescopique',
                'type' => 'Convoyeur',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'ThyssenKrupp',
                'niveau_sil' => 'sil1',
                'fonction_securite' => 'Arret d\'urgence sur derapage bande',
                'description' => 'Acheminement du minerai du parc de stockage au navire',
                'sensors' => [
                    ['code' => 'CAP-QCH-002-01', 'name' => 'Vitesse bande telescopique', 'type' => 'flow', 'unite' => 'm/s', 'seuil_critique' => '0.5', 'last_reading' => 3.2],
                    ['code' => 'CAP-QCH-002-02', 'name' => 'Temperature moteur convoyeur', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '85', 'last_reading' => 58.0],
                ],
            ],
        ]);

        // --- Parc de Stockage Minerai (2 equipements) ---
        $this->createEquipmentWithSensors($zoneStockage, [
            [
                'code' => 'STK-001',
                'name' => 'Stacker-Reclaimer',
                'type' => 'Stacker',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'FLSmidth',
                'niveau_sil' => 'sil1',
                'fonction_securite' => 'Protection collision et arret d\'urgence',
                'description' => 'Mise en tas et reprise du minerai de manganese',
                'sensors' => [
                    ['code' => 'CAP-STK-001-01', 'name' => 'Position roue a godets', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '100', 'last_reading' => 45.0],
                    ['code' => 'CAP-STK-001-02', 'name' => 'Vibration structure stacker', 'type' => 'vibration', 'unite' => 'mm/s', 'seuil_critique' => '10', 'last_reading' => 3.8],
                ],
            ],
            [
                'code' => 'STK-002',
                'name' => 'Bascule Ferroviaire 120T',
                'type' => 'Bascule',
                'type_systeme' => 'process',
                'criticite' => 'moyenne',
                'fabricant' => 'Schenck Process',
                'niveau_sil' => 'na',
                'description' => 'Pesee des wagons de minerai en provenance de Moanda',
                'sensors' => [
                    ['code' => 'CAP-STK-002-01', 'name' => 'Charge bascule', 'type' => 'level', 'unite' => '%', 'seuil_critique' => '100', 'last_reading' => 78.0],
                    ['code' => 'CAP-STK-002-02', 'name' => 'Temperature capteur pesee', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '50', 'last_reading' => 28.0],
                ],
            ],
        ]);

        // --- Station de Criblage Owendo (2 equipements) ---
        $this->createEquipmentWithSensors($zoneCriblage, [
            [
                'code' => 'CRB-001',
                'name' => 'Crible Vibrant Double Pont',
                'type' => 'Crible',
                'type_systeme' => 'process',
                'criticite' => 'haute',
                'fabricant' => 'Metso',
                'niveau_sil' => 'na',
                'description' => 'Criblage final et calibrage du minerai avant expedition',
                'sensors' => [
                    ['code' => 'CAP-CRB-001-01', 'name' => 'Vibration crible pont superieur', 'type' => 'vibration', 'unite' => 'mm/s', 'seuil_critique' => '18', 'last_reading' => 10.2],
                    ['code' => 'CAP-CRB-001-02', 'name' => 'Temperature palier vibreur', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '75', 'last_reading' => 45.0],
                ],
            ],
            [
                'code' => 'CRB-002',
                'name' => 'Alimentateur a Tablier',
                'type' => 'Alimentateur',
                'type_systeme' => 'process',
                'criticite' => 'moyenne',
                'fabricant' => 'Sandvik',
                'niveau_sil' => 'na',
                'description' => 'Alimentation regulee du crible en minerai',
                'sensors' => [
                    ['code' => 'CAP-CRB-002-01', 'name' => 'Vitesse tablier', 'type' => 'flow', 'unite' => 'm/min', 'seuil_critique' => '0.3', 'last_reading' => 2.5],
                    ['code' => 'CAP-CRB-002-02', 'name' => 'Temperature moteur alimentateur', 'type' => 'temperature', 'unite' => '°C', 'seuil_critique' => '80', 'last_reading' => 42.0],
                ],
            ],
        ]);
    }

    private function createEquipmentWithSensors(Zone $zone, array $equipments): void
    {
        foreach ($equipments as $eqData) {
            $sensors = $eqData['sensors'];
            unset($eqData['sensors']);

            $equipment = Equipment::create(array_merge($eqData, [
                'zone_id' => $zone->id,
                'status' => 'operational',
            ]));

            foreach ($sensors as $sensorData) {
                Sensor::create([
                    'equipment_id' => $equipment->id,
                    'code' => $sensorData['code'],
                    'name' => $sensorData['name'],
                    'type' => $sensorData['type'],
                    'unite' => $sensorData['unite'],
                    'seuil_critique' => $sensorData['seuil_critique'],
                    'Dernier_Etallonnage' => now()->subDays(rand(10, 120)),
                    'status' => 'active',
                    'last_reading' => $sensorData['last_reading'],
                    'last_reading_at' => now()->subMinutes(rand(1, 60)),
                ]);
            }
        }
    }
}
