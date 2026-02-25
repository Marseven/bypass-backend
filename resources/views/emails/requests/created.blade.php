<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Nouvelle Requête De ByPass</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 600px;
      margin: 20px auto;
      background: #ffffff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .header {
      padding: 10px;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-content: center;
      justify-content: center;
      color: #fff;
    }
    .header img {
      max-height: 50px;
      margin-bottom: 10px;
    }
    .content {
      padding: 20px;
    }
    .content h1 {
      font-size: 20px;
      margin-bottom: 15px;
      color: #111827;
    }
    .details-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    .details-table th,
    .details-table td {
      padding: 12px;
      border: 1px solid #e5e7eb;
      text-align: left;
      font-size: 14px;
    }
    .details-table th {
      background: #f9fafb;
      font-weight: bold;
    }
    .urgency-high {
      color: #dc2626;
      font-weight: bold;
    }
    .button {
      display: inline-block;
      background: #2563eb;
      color: #fff !important;
      padding: 12px 20px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
    }
    .footer {
      text-align: center;
      padding: 15px;
      font-size: 12px;
      color: #6b7280;
      background: #f9fafb;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <div class="header">
      <img src="{{ asset('logo.png') }}" alt="Logo">
      <h2>Nouvelle Requête Créée 🚨</h2>
    </div>

    <!-- Content -->
    <div class="content">
      <h1>Détails de la requête</h1>
      <table class="details-table">
        <tr>
          <th>Titre</th>
          <td>{{ $request->title }}</td>
        </tr>
        <tr>
          <th>Description</th>
          <td>{{ $request->description }}</td>
        </tr>
        <tr>
          <th>Urgence</th>
          <td class="{{ $request->urgence === 'haute' ? 'urgency-high' : '' }}">
            {{ ucfirst($request->priority) }}
          </td>
        </tr>
        <tr>
          <th>Équipement</th>
          <td>{{ $request->equipment->name }}</td>
        </tr>
        <tr>
          <th>Capteur</th>
          <td>{{ $request->sensor->name }}</td>
        </tr>
        <tr>
          <th>Date</th>
          <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
        </tr>
      </table>

      <p style="text-align: center;">
        <a href="{{ url('/requests/mine') }}" class="button">Voir la requête</a>
      </p>
    </div>

    <!-- Footer -->
    <div class="footer">
      Merci d’utiliser notre application. <br>
      &copy; {{ date('Y') }} COMILOG - Tous droits réservés.
    </div>
  </div>
</body>
</html>
