-- =====================================================
-- SOVIECAP INTERNATIONAL — Base de données complète
-- Importer dans phpMyAdmin après avoir créé la base
-- =====================================================

CREATE DATABASE IF NOT EXISTS camp_vacances CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE camp_vacances;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin','animateur') DEFAULT 'admin',
    actif TINYINT(1) DEFAULT 1,
    derniere_connexion TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    site ENUM('lome','cotonou','accra') DEFAULT 'lome',
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    capacite INT NOT NULL DEFAULT 50,
    prix DECIMAL(12,0) NOT NULL DEFAULT 0,
    description TEXT,
    statut ENUM('ouvert','complet','termine','annule') DEFAULT 'ouvert',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE campeurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    sexe ENUM('M','F') NOT NULL,
    email VARCHAR(150),
    telephone VARCHAR(30),
    adresse TEXT,
    ville VARCHAR(100),
    pays VARCHAR(100) DEFAULT 'Togo',
    -- Tuteur / parent
    tuteur_nom VARCHAR(200),
    tuteur_telephone VARCHAR(30) NOT NULL,
    tuteur_email VARCHAR(150),
    lien_parente VARCHAR(50),
    -- Médical
    groupe_sanguin VARCHAR(10),
    allergies TEXT,
    medicaments TEXT,
    restrictions_alimentaires TEXT,
    medecin_nom VARCHAR(150),
    medecin_telephone VARCHAR(30),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campeur_id INT NOT NULL,
    session_id INT NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente','confirme','annule') DEFAULT 'en_attente',
    montant_total DECIMAL(12,0) DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (campeur_id) REFERENCES campeurs(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id)
);

CREATE TABLE paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inscription_id INT NOT NULL,
    montant DECIMAL(12,0) NOT NULL,
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    mode ENUM('especes','cheque','virement','mobile_money','carte') DEFAULT 'especes',
    reference VARCHAR(100),
    statut ENUM('recu','en_attente','echec') DEFAULT 'recu',
    notes TEXT,
    FOREIGN KEY (inscription_id) REFERENCES inscriptions(id) ON DELETE CASCADE
);

CREATE TABLE personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    poste ENUM('directeur','animateur','moniteur','infirmier','cuisinier','chauffeur','autre') NOT NULL DEFAULT 'animateur',
    site ENUM('lome','cotonou','accra','tous') DEFAULT 'tous',
    email VARCHAR(150),
    telephone VARCHAR(30),
    date_embauche DATE,
    salaire DECIMAL(12,0),
    actif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE activites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    categorie ENUM('sport','arts','nature','jeux','education','sortie','restauration') DEFAULT 'sport',
    site ENUM('lome','cotonou','accra','campus') DEFAULT 'campus',
    duree_minutes INT DEFAULT 90,
    nb_max_participants INT DEFAULT 20,
    lieu VARCHAR(150)
);

CREATE TABLE hebergements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    type ENUM('cabine','dortoir','chambre','chalet') DEFAULT 'dortoir',
    site ENUM('lome','cotonou','accra') DEFAULT 'lome',
    capacite INT NOT NULL DEFAULT 8,
    description TEXT,
    actif TINYINT(1) DEFAULT 1
);

-- ===== COMPTE ADMIN =====
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'Soviecap', 'admin@soviecap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- Mot de passe : password

-- ===== SESSIONS =====
INSERT INTO sessions (nom, site, date_debut, date_fin, capacite, prix, description, statut) VALUES
('Camp Lomé — Session 1 (Juillet 2025)',    'lome',    '2025-07-07','2025-07-28', 50, 150000, 'Session principale Lomé : activités campus, plage, excursions Togo-Bénin', 'ouvert'),
('Camp Cotonou — Session 1 (Juillet 2025)', 'cotonou', '2025-07-07','2025-07-28', 50, 150000, 'Session Cotonou : campus, Fidjrossè, Ganvié, Ouidah', 'ouvert'),
('Camp Accra — Session 1 (Juillet 2025)',   'accra',   '2025-07-07','2025-07-28', 50, 160000, 'Session Accra : campus, Labadi Beach, Cape Coast, Kakum', 'ouvert'),
('Camp Lomé — Session 2 (Août 2025)',       'lome',    '2025-08-04','2025-08-25', 50, 150000, 'Deuxième session Lomé — programme culturel renforcé', 'ouvert'),
('Camp Cotonou — Session 2 (Août 2025)',    'cotonou', '2025-08-04','2025-08-25', 50, 150000, 'Deuxième session Cotonou', 'ouvert');

-- ===== ACTIVITÉS CAMPUS =====
INSERT INTO activites (nom, description, categorie, site, duree_minutes, nb_max_participants, lieu) VALUES
('Football','Tournois et matchs sur le terrain du campus','sport','campus',90,22,'Terrain campus'),
('Basketball','Entraînements et matchs de basketball','sport','campus',90,16,'Terrain campus'),
('Natation','Cours de natation et jeux aquatiques','sport','campus',90,20,'Piscine campus'),
('Volleyball','Tournoi de volleyball','sport','campus',90,16,'Terrain campus'),
('Arts plastiques','Peinture, dessin, sculpture créative','arts','campus',90,20,'Salle arts'),
('Théâtre & Expression','Ateliers scéniques et improvisation','arts','campus',90,20,'Salle polyvalente'),
('Musique & Percussion','Djembé, balafon, instruments africains','arts','campus',90,18,'Salle musique'),
('Danse Afrobeat','Afrobeat, coupé-décalé, azonto','arts','campus',90,25,'Salle danse'),
('Cuisine africaine','Fufu, jollof rice, recettes locales','education','campus',120,15,'Cuisine'),
('Cours d\'anglais','Anglais et langues locales','education','campus',60,20,'Salle de classe'),
('Informatique','Initiation à l\'ordinateur et internet','education','campus',90,20,'Salle info'),
('Jeux de société','Ludo géant, échecs, jeux africains','jeux','campus',60,30,'Salle commune'),
('Soirée cinéma','Films africains sous les étoiles','jeux','campus',180,60,'Cour campus'),
('Feu de camp & Contes','Soirée traditionnelle avec conteurs','jeux','campus',120,60,'Cour campus');

-- ===== ACTIVITÉS LOMÉ =====
INSERT INTO activites (nom, description, categorie, site, duree_minutes, nb_max_participants, lieu) VALUES
('Plage de Lomé','Baignade et jeux de plage','sport','lome',300,40,'Plage de Lomé'),
('Marché de Lomé','Grand marché : artisanat, pagnes, épices','education','lome',180,30,'Marché central'),
('Musée National du Togo','Histoire et culture togolaise','education','lome',150,30,'Musée National'),
('Monument de l\'Indépendance','Visite historique','education','lome',90,30,'Centre Lomé'),
('Marché Akodéssawa','Plus grand marché de fétiches du monde','nature','lome',120,25,'Akodéssawa'),
('Lac Togo & Pirogue','Balade en pirogue traditionnelle','nature','lome',300,25,'Lac Togo'),
('Excursion Kpalimé','Forêt tropicale, cascade, artisanat','nature','lome',480,25,'Kpalimé'),
('Plage d\'Aného','Plage sauvage et vieille ville coloniale','nature','lome',360,25,'Aného'),
('KFC Lomé','Sortie conviviale au KFC','restauration','lome',120,40,'KFC Lomé'),
('Restaurant togolais','Déjeuner gastronomique togolais','restauration','lome',120,30,'Centre Lomé');

-- ===== ACTIVITÉS COTONOU =====
INSERT INTO activites (nom, description, categorie, site, duree_minutes, nb_max_participants, lieu) VALUES
('Plage de Fidjrossè','Plus belle plage de Cotonou','sport','cotonou',300,40,'Fidjrossè'),
('Marché Dantokpa','Plus grand marché d\'Afrique de l\'Ouest','education','cotonou',180,25,'Dantokpa'),
('Village de Ganvié','Village sur l\'eau — patrimoine UNESCO','nature','cotonou',300,25,'Lac Nokoué'),
('Ouidah & Route des esclaves','Temple des Pythons, histoire','education','cotonou',300,25,'Ouidah'),
('Musée da Silva','Culture afro-brésilienne','education','cotonou',120,25,'Cotonou'),
('Abomey & Palais royaux','Palais royaux — patrimoine UNESCO','education','cotonou',480,25,'Abomey'),
('Plage de Grand-Popo','Plage calme et mangrove','nature','cotonou',360,25,'Grand-Popo'),
('KFC Cotonou','Sortie conviviale au KFC','restauration','cotonou',120,40,'KFC Cotonou'),
('Zénith Mall Cotonou','Shopping et loisirs','jeux','cotonou',180,30,'Zénith Mall');

-- ===== ACTIVITÉS ACCRA =====
INSERT INTO activites (nom, description, categorie, site, duree_minutes, nb_max_participants, lieu) VALUES
('Labadi Beach','Plage la plus animée d\'Accra','sport','accra',300,40,'Labadi'),
('Kwame Nkrumah Memorial','Mémorial père de la nation ghanéenne','education','accra',150,30,'Accra centre'),
('Makola Market','Marché central — artisanat ghanéen','education','accra',180,25,'Makola'),
('Fort de Cape Coast','Château esclavagiste — patrimoine UNESCO','education','accra',480,25,'Cape Coast'),
('Kakum National Park','Canopée — ponts suspendus en forêt tropicale','nature','accra',480,25,'Kakum'),
('Boti Falls','Chutes d\'eau en pleine forêt','nature','accra',360,25,'Boti'),
('Aburi Botanical Gardens','Jardins botaniques dans les collines','nature','accra',300,25,'Aburi'),
('Arts Centre & Musée National','Artisanat et musée du Ghana','education','accra',180,25,'Accra centre'),
('KFC Accra','Sortie conviviale au KFC','restauration','accra',120,40,'KFC Accra'),
('Silverbird Cinéma & Accra Mall','Cinéma et shopping','jeux','accra',240,35,'Accra Mall'),
('Plage de Busua','Surf et plage paradisiaque','sport','accra',360,25,'Busua');

-- ===== HÉBERGEMENTS =====
INSERT INTO hebergements (nom, type, site, capacite, description) VALUES
('Dortoir A — Lomé','dortoir','lome',16,'Dortoir 16 places avec sanitaires communs'),
('Dortoir B — Lomé','dortoir','lome',16,'Dortoir 16 places avec sanitaires communs'),
('Chambre Encadrants — Lomé','chambre','lome',4,'Réservé au personnel encadrant'),
('Dortoir A — Cotonou','dortoir','cotonou',16,'Dortoir 16 places avec sanitaires communs'),
('Dortoir B — Cotonou','dortoir','cotonou',16,'Dortoir 16 places avec sanitaires communs'),
('Chambre Encadrants — Cotonou','chambre','cotonou',4,'Réservé au personnel encadrant'),
('Dortoir A — Accra','dortoir','accra',16,'Dortoir 16 places avec sanitaires communs'),
('Dortoir B — Accra','dortoir','accra',16,'Dortoir 16 places avec sanitaires communs'),
('Chambre Encadrants — Accra','chambre','accra',4,'Réservé au personnel encadrant');
