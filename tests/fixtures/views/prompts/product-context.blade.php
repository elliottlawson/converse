Product Context:
- Name: {{ $product['name'] }}
- Type: {{ $product['type'] }}
- Stage: {{ $product['stage'] }}

Focus on {{ $product['stage'] === 'MVP' ? 'rapid development and core features' : 'scalability and optimization' }}.