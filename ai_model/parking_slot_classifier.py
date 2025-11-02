"""
Smart Parking Slot Classifier
A basic classification model to find the optimal parking spot based on:
- Proximity to destination
- Slot popularity (historical usage)
- User feedback scores
"""

import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler
import pickle
import json
from typing import List, Dict, Tuple


class ParkingSlotClassifier:
    """
    Basic ML model to classify and rank parking slots based on multiple factors
    """
    
    def __init__(self):
        self.model = RandomForestClassifier(
            n_estimators=100,
            max_depth=10,
            random_state=42
        )
        self.scaler = StandardScaler()
        self.is_trained = False
        
    def calculate_proximity_score(self, slot_coords: Tuple[float, float], 
                                   user_coords: Tuple[float, float]) -> float:
        """
        Calculate distance-based proximity score using Haversine formula
        Returns score between 0-1 (1 being closest)
        """
        lat1, lon1 = slot_coords
        lat2, lon2 = user_coords
        
        # Haversine formula
        R = 6371  # Earth's radius in kilometers
        
        lat1_rad = np.radians(lat1)
        lat2_rad = np.radians(lat2)
        delta_lat = np.radians(lat2 - lat1)
        delta_lon = np.radians(lon2 - lon1)
        
        a = np.sin(delta_lat/2)**2 + np.cos(lat1_rad) * np.cos(lat2_rad) * np.sin(delta_lon/2)**2
        c = 2 * np.arctan2(np.sqrt(a), np.sqrt(1-a))
        distance = R * c
        
        # Convert to score (inverse relationship - closer is better)
        # Max distance considered: 5km
        proximity_score = max(0, 1 - (distance / 5.0))
        return proximity_score
    
    def prepare_features(self, slots_data: List[Dict]) -> np.ndarray:
        """
        Prepare feature matrix from slot data
        Features:
        1. Proximity score (0-1)
        2. Average feedback score (0-5)
        3. Popularity score (normalized booking count)
        4. Availability score (1 if available, 0 if not)
        5. Price factor (normalized)
        """
        features = []
        
        for slot in slots_data:
            feature_vector = [
                slot.get('proximity_score', 0.5),
                slot.get('avg_feedback', 3.0) / 5.0,  # Normalize to 0-1
                slot.get('popularity_score', 0.5),
                1.0 if slot.get('is_available', True) else 0.0,
                slot.get('price_factor', 0.5)
            ]
            features.append(feature_vector)
        
        return np.array(features)
    
    def train(self, training_data: List[Dict], labels: List[int]):
        """
        Train the model with historical data
        labels: 1 if slot was chosen and user was satisfied, 0 otherwise
        """
        X = self.prepare_features(training_data)
        X_scaled = self.scaler.fit_transform(X)
        
        self.model.fit(X_scaled, labels)
        self.is_trained = True
        print(f"Model trained with {len(training_data)} samples")
    
    def predict_best_slots(self, slots_data: List[Dict], 
                           user_location: Tuple[float, float],
                           top_k: int = 3) -> List[Dict]:
        """
        Predict and return top K best parking slots
        """
        # Calculate proximity scores for all slots
        for slot in slots_data:
            slot_coords = (slot['latitude'], slot['longitude'])
            slot['proximity_score'] = self.calculate_proximity_score(
                slot_coords, user_location
            )
        
        # If model is trained, use it for prediction
        if self.is_trained:
            X = self.prepare_features(slots_data)
            X_scaled = self.scaler.transform(X)
            
            # Get probability scores for positive class
            scores = self.model.predict_proba(X_scaled)[:, 1]
        else:
            # Fallback to weighted scoring if model not trained
            scores = self._calculate_weighted_scores(slots_data)
        
        # Add scores to slots
        for i, slot in enumerate(slots_data):
            slot['recommendation_score'] = float(scores[i])
        
        # Sort by score and return top K
        sorted_slots = sorted(slots_data, 
                            key=lambda x: x['recommendation_score'], 
                            reverse=True)
        
        return sorted_slots[:top_k]
    
    def _calculate_weighted_scores(self, slots_data: List[Dict]) -> np.ndarray:
        """
        Fallback weighted scoring when model is not trained
        """
        scores = []
        for slot in slots_data:
            # Weights for different factors
            score = (
                slot.get('proximity_score', 0.5) * 0.4 +  # 40% weight to proximity
                (slot.get('avg_feedback', 3.0) / 5.0) * 0.3 +  # 30% to feedback
                slot.get('popularity_score', 0.5) * 0.2 +  # 20% to popularity
                (1.0 if slot.get('is_available', True) else 0.0) * 0.1  # 10% availability
            )
            scores.append(score)
        
        return np.array(scores)
    
    def save_model(self, filepath: str):
        """Save trained model to disk"""
        model_data = {
            'model': self.model,
            'scaler': self.scaler,
            'is_trained': self.is_trained
        }
        with open(filepath, 'wb') as f:
            pickle.dump(model_data, f)
        print(f"Model saved to {filepath}")
    
    def load_model(self, filepath: str):
        """Load trained model from disk"""
        with open(filepath, 'rb') as f:
            model_data = pickle.load(f)
        
        self.model = model_data['model']
        self.scaler = model_data['scaler']
        self.is_trained = model_data['is_trained']
        print(f"Model loaded from {filepath}")


def generate_training_data(num_samples: int = 100) -> Tuple[List[Dict], List[int]]:
    """
    Generate synthetic training data for initial model training
    In production, this would come from real user feedback
    """
    slots_data = []
    labels = []
    
    for i in range(num_samples):
        # Generate random slot data
        slot = {
            'slot_id': f'SLOT_{i}',
            'latitude': 28.6139 + np.random.uniform(-0.1, 0.1),
            'longitude': 77.2090 + np.random.uniform(-0.1, 0.1),
            'proximity_score': np.random.uniform(0, 1),
            'avg_feedback': np.random.uniform(2, 5),
            'popularity_score': np.random.uniform(0, 1),
            'is_available': np.random.choice([True, False], p=[0.7, 0.3]),
            'price_factor': np.random.uniform(0.3, 1.0)
        }
        
        # Generate label based on features (higher quality slots more likely to be chosen)
        quality_score = (
            slot['proximity_score'] * 0.4 +
            (slot['avg_feedback'] / 5.0) * 0.3 +
            slot['popularity_score'] * 0.2 +
            (1.0 if slot['is_available'] else 0.0) * 0.1
        )
        
        label = 1 if quality_score > 0.6 else 0
        
        slots_data.append(slot)
        labels.append(label)
    
    return slots_data, labels


if __name__ == "__main__":
    # Example usage
    print("Initializing Parking Slot Classifier...")
    classifier = ParkingSlotClassifier()
    
    # Generate and train with synthetic data
    print("\nGenerating training data...")
    training_data, labels = generate_training_data(200)
    
    print("\nTraining model...")
    classifier.train(training_data, labels)
    
    # Example prediction
    print("\nTesting prediction...")
    test_slots = [
        {
            'slot_id': 'A1',
            'latitude': 28.6139,
            'longitude': 77.2090,
            'avg_feedback': 4.5,
            'popularity_score': 0.8,
            'is_available': True,
            'price_factor': 0.7
        },
        {
            'slot_id': 'B2',
            'latitude': 28.6150,
            'longitude': 77.2100,
            'avg_feedback': 3.2,
            'popularity_score': 0.4,
            'is_available': True,
            'price_factor': 0.5
        },
        {
            'slot_id': 'C3',
            'latitude': 28.6145,
            'longitude': 77.2095,
            'avg_feedback': 4.8,
            'popularity_score': 0.9,
            'is_available': True,
            'price_factor': 0.9
        }
    ]
    
    user_location = (28.6139, 77.2090)
    best_slots = classifier.predict_best_slots(test_slots, user_location, top_k=3)
    
    print("\nTop 3 Recommended Slots:")
    for i, slot in enumerate(best_slots, 1):
        print(f"{i}. {slot['slot_id']} - Score: {slot['recommendation_score']:.3f}")
    
    # Save model
    classifier.save_model('parking_model.pkl')
