/**
 * Carte Interactive Leaflet.js - Résidences Seniors
 * Version propre sans logs de debug
 */

class ResidenceMap {
    constructor(containerId, residences, baseUrl) {
        this.containerId = containerId;
        this.residences = residences;
        this.baseUrl = baseUrl;
        this.map = null;
        this.markersGroup = null;
        this.defaultCenter = [46.603354, 1.888334]; // Centre de la France
        this.defaultZoom = 6;
        this.colorMode = false; // Mode couleur désactivé par défaut
        
        this.init();
    }
    
    /**
     * Initialiser la carte
     */
    init() {
        // Créer la carte
        this.map = L.map(this.containerId).setView(this.defaultCenter, this.defaultZoom);
        
        // Ajouter le fond de carte OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18,
            minZoom: 5
        }).addTo(this.map);
        
        // Groupe de marqueurs
        this.markersGroup = L.featureGroup().addTo(this.map);
        
        // Ajouter les marqueurs
        this.addMarkers();
        
        // Ajuster la vue pour afficher tous les marqueurs
        this.fitBounds();
        
        // Initialiser les contrôles
        this.initControls();
    }
    
    /**
     * Obtenir la classe badge selon le taux
     */
    getBadgeClass(taux) {
        if (taux >= 80) return 'success';
        if (taux >= 50) return 'warning';
        return 'danger';
    }
    
    /**
     * Obtenir la couleur du marqueur selon le taux d'occupation
     */
    getMarkerColor(taux) {
        if (taux >= 80) return '#28a745'; // Vert
        if (taux >= 50) return '#ffc107'; // Jaune
        return '#dc3545'; // Rouge
    }
    
    /**
     * Créer une icône de marqueur colorée avec Font Awesome
     */
    createColoredIcon(color) {
        return L.divIcon({
            className: 'custom-marker',
            html: `<i class="fas fa-map-marker-alt" style="
                font-size: 40px;
                color: ${color};
                text-shadow: 0 2px 4px rgba(0,0,0,0.4);
            "></i>`,
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });
    }
    
    /**
     * Créer le contenu du popup pour une résidence - TEXTE AGRANDI ET CENTRÉ
     */
    createPopupContent(residence) {
        return `
            <div style="min-width: 280px; background: white; padding: 10px;">
                <h5 class="mb-3 text-center text-dark" style="font-size: 1.1rem; font-weight: 600;">
                    <i class="fas fa-building text-danger me-2"></i>
                    ${residence.nom}
                </h5>
                <hr class="my-2">
                <p class="mb-2 text-center" style="font-size: 0.95rem; line-height: 1.6;">
                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                    <strong>${residence.adresse}</strong><br>
                    <span>${residence.code_postal} ${residence.ville}</span>
                </p>
                <p class="mb-2 text-center" style="font-size: 0.95rem;">
                    <i class="fas fa-building text-primary me-2"></i>
                    <strong>Exploitant:</strong> ${residence.exploitant || 'Non assigné'}
                </p>
                <hr class="my-2">
                <div class="row text-center mb-3">
                    <div class="col-6">
                        <div style="font-size: 1.3rem; font-weight: bold; color: #0d6efd;">
                            <i class="fas fa-door-open"></i> ${residence.nb_lots}
                        </div>
                        <small class="text-muted">Lots</small>
                    </div>
                    <div class="col-6">
                        <div style="font-size: 1.3rem; font-weight: bold; color: #198754;">
                            <i class="fas fa-users"></i> ${residence.nb_occupations}
                        </div>
                        <small class="text-muted">Occupations</small>
                    </div>
                </div>
                <div class="text-center mb-3">
                    <span class="badge bg-${this.getBadgeClass(residence.taux_occupation)}" style="font-size: 1rem; padding: 8px 16px;">
                        Taux: ${Number(residence.taux_occupation).toFixed(1)}%
                    </span>
                </div>
                <a href="${this.baseUrl}/admin/viewResidence/${residence.id}" 
                   class="btn btn-danger w-100" 
                   style="font-size: 1rem; padding: 10px;">
                    <i class="fas fa-eye me-2"></i> Voir les détails
                </a>
            </div>
        `;
    }
    
    /**
     * Ajouter tous les marqueurs sur la carte
     */
    addMarkers() {
        this.residences.forEach(residence => {
            // Vérifier si les coordonnées GPS existent
            if (residence.latitude && residence.longitude) {
                const lat = parseFloat(residence.latitude);
                const lng = parseFloat(residence.longitude);
                
                // Créer le marqueur avec icône selon le mode
                let marker;
                if (this.colorMode) {
                    const color = this.getMarkerColor(residence.taux_occupation);
                    const icon = this.createColoredIcon(color);
                    marker = L.marker([lat, lng], { icon: icon });
                } else {
                    marker = L.marker([lat, lng]);
                }
                
                // Ajouter le popup
                marker.bindPopup(this.createPopupContent(residence));
                
                // Ajouter au groupe
                marker.addTo(this.markersGroup);
            }
        });
    }
    
    /**
     * Ajuster la vue pour afficher tous les marqueurs
     */
    fitBounds() {
        if (this.markersGroup.getLayers().length > 0) {
            this.map.fitBounds(this.markersGroup.getBounds(), { padding: [50, 50], maxZoom: 15 });
        }
    }
    
    /**
     * Réinitialiser la vue
     */
    resetView() {
        if (this.markersGroup.getLayers().length > 0) {
            this.fitBounds();
        } else {
            this.map.setView(this.defaultCenter, this.defaultZoom);
        }
    }
    
    /**
     * Zoom avant
     */
    zoomIn() {
        this.map.zoomIn();
    }
    
    /**
     * Zoom arrière
     */
    zoomOut() {
        this.map.zoomOut();
    }
    
    /**
     * Basculer entre marqueurs standard et colorés
     */
    toggleColorMode() {
        this.colorMode = !this.colorMode;
        
        // Rafraîchir les marqueurs
        this.markersGroup.clearLayers();
        this.addMarkers();
        
        // Mettre à jour le bouton
        const btn = document.getElementById('btnToggleColors');
        if (btn) {
            if (this.colorMode) {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary');
                btn.innerHTML = '<i class="fas fa-palette"></i> Code couleur ON';
            } else {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
                btn.innerHTML = '<i class="fas fa-palette"></i> Code couleur';
            }
        }
    }
    
    /**
     * Initialiser les boutons de contrôle
     */
    initControls() {
        const btnZoomIn = document.getElementById('btnZoomIn');
        const btnZoomOut = document.getElementById('btnZoomOut');
        const btnResetView = document.getElementById('btnResetView');
        const btnToggleColors = document.getElementById('btnToggleColors');
        
        if (btnZoomIn) {
            btnZoomIn.addEventListener('click', () => this.zoomIn());
        }
        
        if (btnZoomOut) {
            btnZoomOut.addEventListener('click', () => this.zoomOut());
        }
        
        if (btnResetView) {
            btnResetView.addEventListener('click', () => this.resetView());
        }
        
        if (btnToggleColors) {
            btnToggleColors.addEventListener('click', () => this.toggleColorMode());
        }
    }
    
    /**
     * Centrer la carte sur une résidence spécifique
     */
    centerOnResidence(residenceId) {
        const residence = this.residences.find(r => r.id == residenceId);
        if (residence && residence.latitude && residence.longitude) {
            this.map.setView([residence.latitude, residence.longitude], 15);
        }
    }
    
    /**
     * Filtrer les marqueurs par critères
     */
    filterMarkers(criteria) {
        this.markersGroup.clearLayers();
        
        const filtered = this.residences.filter(residence => {
            // Filtre par ville
            if (criteria.ville && residence.ville !== criteria.ville) {
                return false;
            }
            
            // Filtre par taux d'occupation
            if (criteria.tauxMin && residence.taux_occupation < criteria.tauxMin) {
                return false;
            }
            
            if (criteria.tauxMax && residence.taux_occupation > criteria.tauxMax) {
                return false;
            }
            
            // Filtre par exploitant
            if (criteria.exploitant && residence.exploitant !== criteria.exploitant) {
                return false;
            }
            
            return true;
        });
        
        // Ajouter les marqueurs filtrés
        filtered.forEach(residence => {
            if (residence.latitude && residence.longitude) {
                const marker = L.marker([residence.latitude, residence.longitude]);
                marker.bindPopup(this.createPopupContent(residence));
                marker.addTo(this.markersGroup);
            }
        });
        
        // Ajuster la vue
        this.fitBounds();
        
        return filtered.length;
    }
    
    /**
     * Réinitialiser les filtres (afficher tous les marqueurs)
     */
    resetFilters() {
        this.markersGroup.clearLayers();
        this.addMarkers();
        this.fitBounds();
    }
}

// Export pour utilisation globale
window.ResidenceMap = ResidenceMap;
