# Semestre4-R401-APIRest

# Objectif
L’objectif de ce projet est de reprendre le projet de gestion d’un cabinet médical proposé dans la ressource R3.01 du semestre 3, et d’externaliser les traitements relatifs à la gestion des usagers, des médecins, et des consultations à différentes API REST.

# Travail à réaliser
La figure ci-dessous décrit l’architecture générale du projet.
Deux bases de données distinctes doivent être mises en œuvre : l’une contiendra les utilisateurs autorisés à interagir avec les API du cabinet médical ; l’autre contiendra les informations sur les usagers, les médecins et les consultations. L’objectif de ces deux bases de données est de clairement séparer les parties “authentification” et “traitement métier”.

Les API à concevoir sont détaillées ci-dessous.

# API authentification
Cette API est responsable de la création des jetons qui doivent être transmis aux API de gestion du cabinet médical. La base de données associée à cette API contient donc les identifiants des 
secrétaires autorisé·e·s à accéder aux API de gestion du cabinet médical.

# API de gestion de la ressource usagers
Cette API est responsable de la gestion (i.e., création, modification, mise à jour, suppression) des usagers du cabinet médical. Pour être manipulée, elle requiert un jeton valide d’authentification.

# API de gestion de la ressource médecins
Cette API est responsable de la gestion (i.e., création, modification, mise à jour, suppression) des médecins du cabinet médical. Pour être manipulée, elle requiert un jeton valide d’authentification.

# API de gestion de la ressource consultations
Cette API est responsable de la gestion (i.e., création, modification, mise à jour, suppression) des consultations du cabinet médical. La contrainte de non chevauchement de deux consultations pour un même médecin proposée dans le projet du semestre 3 doit être préservée. Pour être manipulée, cette API requiert un jeton valide d’authentification.

# API de demande de statistiques
La demande d’obtention des statistiques ne respecte pas le principe CRUD, nous ne pouvons pas considérer cette API comme étant Rest. Malgré tout, pour respecter un format général cohérent, il sera nécessaire de prévoir un endpoint spécifique comme si les stats étaient une ressource à part entière.
