"""
Defaults Manager - Gère les valeurs par défaut personnalisées de l'application.

Les valeurs par défaut sont stockées dans config/defaults.json et peuvent être modifiées
par l'utilisateur à partir de la fenêtre de configuration.
"""

import json
import logging
from pathlib import Path
from typing import Dict, Any, Optional, List

logger = logging.getLogger(__name__)


class DefaultsManager:
    """Gestionnaire centralisé pour les valeurs par défaut de l'application."""

    def __init__(self):
        """Initialise le gestionnaire des défauts."""
        self.defaults_path = Path(__file__).resolve().parent.parent.parent / 'config' / 'defaults.json'
        self.defaults = self._load_defaults()

    def _load_defaults(self) -> Dict[str, Any]:
        """Charge les valeurs par défaut depuis le fichier de configuration."""
        initial_defaults = self._get_initial_defaults()
        try:
            if self.defaults_path.exists():
                with self.defaults_path.open('r', encoding='utf-8') as f:
                    loaded_defaults = json.load(f)
                    if isinstance(loaded_defaults, dict):
                        return self._merge_with_initial_defaults(initial_defaults, loaded_defaults)
        except Exception as e:
            logger.warning(f"Impossible de charger les défauts: {e}")
        
        # Retourner les défauts initiaux si le fichier n'existe pas
        return initial_defaults

    def _merge_with_initial_defaults(self, base_defaults: Dict[str, Any], loaded_defaults: Dict[str, Any]) -> Dict[str, Any]:
        """Fusionne les défauts chargés avec les clés initiales pour gérer les nouvelles options."""
        merged: Dict[str, Any] = {}

        for section, section_values in base_defaults.items():
            loaded_section = loaded_defaults.get(section, {})
            if not isinstance(loaded_section, dict):
                loaded_section = {}
            merged_section = dict(section_values)
            merged_section.update(loaded_section)
            merged[section] = merged_section

        # Conserver les sections additionnelles non prévues.
        for section, section_values in loaded_defaults.items():
            if section not in merged and isinstance(section_values, dict):
                merged[section] = dict(section_values)

        return merged

    def _get_initial_defaults(self) -> Dict[str, Any]:
        """Retourne les défauts initiaux basés sur les constantes."""
        from . import constants
        
        return {
            'societe': {
                'DenSte': constants.DenSte[0] if constants.DenSte else '',
                'FormJur': constants.Formjur[0] if constants.Formjur else '',
                'Ice': '',
                'DateIce': '',
                'DateExpCertNeg': '',
                'Capital': constants.Capital[0] if constants.Capital else '',
                'PartsSocial': constants.PartsSocial[0] if constants.PartsSocial else '',
                'ValeurNominale': '100',
                'SteAdresse': constants.SteAdresse[0] if constants.SteAdresse else '',
                'Tribunal': constants.Tribunnaux[0] if constants.Tribunnaux else '',
            },
            'associe': {
                'Civility': constants.Civility[0] if constants.Civility else '',
                'Nom': 'NOM',
                'Prenom': 'PRENOM',
                'Nationality': constants.Nationalite[0] if constants.Nationalite else '',
                'NumPiece': '',
                'Adresse': '',
                'Telephone': '',
                'Email': '',
                'Quality': constants.QualityGerant[0] if constants.QualityGerant else '',
            },
            'contrat': {
                'NbMois': constants.Nbmois[1] if len(constants.Nbmois) > 1 else (constants.Nbmois[0] if constants.Nbmois else ''),
                'TypeContratDomiciliation': constants.TypeContratDomiciliation[0] if constants.TypeContratDomiciliation else '',
                'TypeRenouvellement': constants.TypeRenouvellement[0] if constants.TypeRenouvellement else '',
                'Tva': '20',
                'DhHt': '83.3333',
                'TvaRenouvellement': '20',
                'DhHtRenouvellement': '166.667',
            }
        }

    def get_default(self, section: str, key: str) -> Optional[str]:
        """Obtient une valeur par défaut.
        
        Args:
            section: La section (societe, associe, contrat)
            key: La clé dans la section
            
        Returns:
            La valeur par défaut ou None
        """
        return self.defaults.get(section, {}).get(key)

    def set_default(self, section: str, key: str, value: str) -> None:
        """Définit une valeur par défaut.
        
        Args:
            section: La section (societe, associe, contrat)
            key: La clé dans la section
            value: La nouvelle valeur
        """
        if section not in self.defaults:
            self.defaults[section] = {}
        self.defaults[section][key] = value
        self._save_defaults()

    def get_all_defaults(self) -> Dict[str, Any]:
        """Retourne tous les défauts."""
        return self.defaults.copy()

    def set_all_defaults(self, defaults: Dict[str, Any]) -> None:
        """Définit tous les défauts.
        
        Args:
            defaults: Dictionnaire complet des défauts
        """
        self.defaults = defaults
        self._save_defaults()

    def reset_to_initial(self) -> None:
        """Réinitialise les défauts aux valeurs initiales."""
        self.defaults = self._get_initial_defaults()
        self._save_defaults()
        logger.info("Défauts réinitialisés aux valeurs initiales")

    def _save_defaults(self) -> None:
        """Sauvegarde les défauts dans le fichier de configuration."""
        try:
            self.defaults_path.parent.mkdir(parents=True, exist_ok=True)
            with self.defaults_path.open('w', encoding='utf-8') as f:
                json.dump(self.defaults, f, ensure_ascii=False, indent=2)
            logger.info("Défauts sauvegardés avec succès")
        except Exception as e:
            logger.error(f"Impossible de sauvegarder les défauts: {e}")
            raise

    def get_defaults_by_section(self, section: str) -> Dict[str, str]:
        """Obtient tous les défauts d'une section.
        
        Args:
            section: La section (societe, associe, contrat)
            
        Returns:
            Dictionnaire des défauts pour la section
        """
        return self.defaults.get(section, {}).copy()

    def export_defaults(self) -> str:
        """Exporte les défauts en JSON."""
        return json.dumps(self.defaults, ensure_ascii=False, indent=2)

    def import_defaults(self, json_str: str) -> bool:
        """Importe les défauts depuis une chaîne JSON.
        
        Args:
            json_str: Chaîne JSON contenant les défauts
            
        Returns:
            True si l'import a réussi, False sinon
        """
        try:
            defaults = json.loads(json_str)
            self.set_all_defaults(defaults)
            return True
        except Exception as e:
            logger.error(f"Impossible d'importer les défauts: {e}")
            return False


# Instance globale du gestionnaire des défauts
_defaults_manager_instance: Optional[DefaultsManager] = None


def get_defaults_manager() -> DefaultsManager:
    """Retourne l'instance globale du gestionnaire des défauts."""
    global _defaults_manager_instance
    if _defaults_manager_instance is None:
        _defaults_manager_instance = DefaultsManager()
    return _defaults_manager_instance
