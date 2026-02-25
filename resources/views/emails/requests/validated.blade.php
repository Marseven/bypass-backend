<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Demande {{ $status === 'approved' ? 'Approuvée' : 'Rejetée' }}</title>
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
      background: {{ $status === 'approved' ? '#10b981' : '#ef4444' }};
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
    .status-badge {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 6px;
      font-weight: bold;
      margin-bottom: 20px;
      background: {{ $status === 'approved' ? '#d1fae5' : '#fee2e2' }};
      color: {{ $status === 'approved' ? '#065f46' : '#991b1b' }};
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
      <h2>Demande {{ $status === 'approved' ? 'Approuvée ✅' : 'Rejetée ❌' }}</h2>
    </div>

    <!-- Content -->
    <div class="content">
      <h1>Détails de la demande</h1>
      <div class="status-badge">
        {{ $status === 'approved' ? 'Approuvée' : 'Rejetée' }}
        @if($validationLevel)
          (Validation niveau {{ $validationLevel }})
        @endif
      </div>
      
      <table class="details-table">
        <tr>
          <th>Code de la demande</th>
          <td>{{ $request->request_code }}</td>
        </tr>
        <tr>
          <th>Titre</th>
          <td>{{ $request->title }}</td>
        </tr>
        <tr>
          <th>Description</th>
          <td>{{ $request->description }}</td>
        </tr>
        <tr>
          <th>Priorité</th>
          <td>{{ ucfirst($request->priority) }}</td>
        </tr>
        <tr>
          <th>Équipement</th>
          <td>{{ $request->equipment->name ?? 'N/A' }}</td>
        </tr>
        <tr>
          <th>Capteur</th>
          <td>{{ $request->sensor->name ?? 'N/A' }}</td>
        </tr>
        @if($status === 'rejected' && $rejectionReason)
        <tr>
          <th>Raison du rejet</th>
          <td>{{ $rejectionReason }}</td>
        </tr>
        @endif
        <tr>
          <th>Date de validation</th>
          <td>{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
      </table>

      <p style="text-align: center;">
        <a href="{{ url('/requests/mine') }}" class="button">Voir mes demandes</a>
      </p>
    </div>

    <!-- Footer -->
    <div class="footer">
      Merci d'utiliser notre application. <br>
      &copy; {{ date('Y') }} COMILOG - Tous droits réservés.
    </div>
  </div>
</body>
</html>

