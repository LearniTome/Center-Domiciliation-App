-- Types de cabinets et identifiants legaux complementaires.
--
-- Parite avec `collaborateurs`, dont les cabinets ne reprenaient pas les
-- telephones dedies, la qualification, la fonction, l'identifiant fiscal (IF)
-- ni la taxe professionnelle (TP) :
--   collaborateurs.collaborateur_tel_fixe   -> cabinets.telephone_fixe
--   collaborateurs.collaborateur_tel_mobile -> cabinets.telephone_mobile
--   collaborateurs.fonction                 -> cabinets.fonction
--   collaborateurs.qualite_intermediaire_id -> cabinets.qualification (texte)
--   collaborateurs.collaborateur_if         -> cabinets.identifiant_fiscal
--   collaborateurs.collaborateur_tp         -> cabinets.taxe_professionnelle
--
-- `qualification` est un texte libre et non une cle vers
-- `ref_qualites_intermediaire` : cette table n'a ni Avocat ni Notaire, deux des
-- cinq types exiges, et son axe est la qualite d'un intermediaire, pas la
-- nature du cabinet.
--
-- `type_cabinet` reste NULLABLE : la colonne est obligatoire cote formulaire
-- et cote serveur, mais les cabinets deja en production n'ont pas de type connu.
-- Les imposing a la migration, il aurait fallu inventer une valeur pour chaque
-- dossier existant. Un cabinet sans type s'affiche desormais « Non renseigne »
-- et n'est pas proposé par le filtre, ce qui laisse le Centre le renseigner.
--
-- Un seul ALTER TABLE : le runner envoie le fichier d'un bloc et abandonne les
-- instructions suivantes a la premiere erreur. Regrouper colonnes et index rend
-- l'ajout atomique, et un rejeu echoue d'un coup (ignore comme doublon) au lieu
-- de laisser l'index derriere.

ALTER TABLE cabinets
    ADD COLUMN type_cabinet VARCHAR(40) NULL DEFAULT NULL AFTER code,
    ADD COLUMN telephone_fixe VARCHAR(60) NULL DEFAULT NULL AFTER telephone,
    ADD COLUMN telephone_mobile VARCHAR(60) NULL DEFAULT NULL AFTER telephone_fixe,
    ADD COLUMN qualification VARCHAR(150) NULL DEFAULT NULL AFTER telephone_mobile,
    ADD COLUMN fonction VARCHAR(150) NULL DEFAULT NULL AFTER qualification,
    ADD COLUMN identifiant_fiscal VARCHAR(100) NULL DEFAULT NULL AFTER rc,
    ADD COLUMN taxe_professionnelle VARCHAR(100) NULL DEFAULT NULL AFTER identifiant_fiscal,
    ADD KEY idx_cabinets_type (type_cabinet);
