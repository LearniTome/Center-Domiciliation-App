-- Responsable principal du cabinet.
--
-- Le formulaire de creation ne portait que la denomination du cabinet et ses
-- identifiants legaux (ICE / RC / IF / TP) : rien n'identifiait la personne a
-- contacter chez le client. Sur un cabinet d'expertise comptable ou un conseil
-- juridique, c'est pourtant cette personne qui signe les pieces, recoit les
-- relances et depose les declarations — le nom du cabinet seul ne suffit pas.
--
-- Ces colonnes sont volontairement des attributs du cabinet, et non un lien
-- vers `collaborateurs` : a la creation le cabinet n'a encore aucun
-- collaborateur rattache, donc un select serait vide et inverserait l'ordre
-- naturel du formulaire (creer le cabinet, puis les collaborateurs). La
-- fiche `collaborateurs` reste la source de verite des dossiers ; ces quatre
-- colonnes ne portent que l'interlocuteur commercial.
--
-- Toutes nullable : la migration ne peut pas retro-igner les cabinets deja
-- enregistres, et le seul champ obligatoire (`responsable_nom`) est controle
-- par le formulaire, pas par la base.
--
-- Un seul bloc ALTER : le runner envoie le fichier d'un trait et s'arrete a la
-- premiere erreur. L'ALTER est atomique, donc les quatre colonnes partent ou
-- aucune — un cabinet ne se retrouve jamais avec un responsable a moitie saisi.
-- L'erreur 1060 « duplicate column » (42S21), si la migration a deja ete
-- appliquee, est explicitement toleree par le runner.

ALTER TABLE cabinets
    ADD COLUMN responsable_nom VARCHAR(150) DEFAULT NULL AFTER type_cabinet,
    ADD COLUMN responsable_fonction VARCHAR(120) DEFAULT NULL AFTER responsable_nom,
    ADD COLUMN responsable_email VARCHAR(190) DEFAULT NULL AFTER responsable_fonction,
    ADD COLUMN responsable_telephone VARCHAR(40) DEFAULT NULL AFTER responsable_email;
