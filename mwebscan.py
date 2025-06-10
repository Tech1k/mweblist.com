import requests
import sqlite3
import json
import time

RPC_URL = 'http://NODEIP:9332/'
RPC_USER = 'RPC_USER'
RPC_PASSWORD = 'RPC_PASS'
COMMIT_EVERY_N_BLOCKS = 100

conn = sqlite3.connect('mweblist.db')
cursor = conn.cursor()
conn.execute("PRAGMA journal_mode = WAL;")
conn.execute("PRAGMA synchronous = OFF;")

cursor.execute('''
    CREATE TABLE IF NOT EXISTS mweb_pegins (
        txid TEXT PRIMARY KEY,
        block_height INTEGER,
        amount REAL
    )
''')
cursor.execute('''
    CREATE TABLE IF NOT EXISTS scan_progress (
        id INTEGER PRIMARY KEY,
        last_scanned_block INTEGER
    )
''')
cursor.execute('''
    CREATE TABLE IF NOT EXISTS mweb_total (
        id INTEGER PRIMARY KEY,
        mweb_total REAL
    )
''')
conn.commit()

def rpc_request(method, params=None):
    payload = json.dumps({
        'jsonrpc': '1.0',
        'id': 'python',
        'method': method,
        'params': params or []
    })
    response = requests.post(
        RPC_URL,
        data=payload,
        headers={'Content-Type': 'text/plain'},
        auth=(RPC_USER, RPC_PASSWORD)
    )
    response.raise_for_status()
    return response.json()['result']

def get_last_scanned_block():
    cursor.execute("SELECT last_scanned_block FROM scan_progress WHERE id = 1")
    row = cursor.fetchone()
    return int(row[0]) if row else 2265950

def set_last_scanned_block(height):
    cursor.execute('''
        INSERT INTO scan_progress (id, last_scanned_block) VALUES (1, ?)
        ON CONFLICT(id) DO UPDATE SET last_scanned_block=excluded.last_scanned_block
    ''', (height,))

def set_mweb_total(total):
    cursor.execute('''
        INSERT INTO mweb_total (id, mweb_total) VALUES (1, ?)
        ON CONFLICT(id) DO UPDATE SET mweb_total=excluded.mweb_total
    ''', (total,))

def scan_blocks():
    start = get_last_scanned_block() + 1
    tip = rpc_request('getblockcount')

    if tip < start:
        return

    batch_pegins = []
    mweb_total_running = 0.0

    for height in range(start, tip + 1):
        blockhash = rpc_request('getblockhash', [height])
        block = rpc_request('getblock', [blockhash, 2])

        print(f"Scanning block {height}...")
        peg_in_count = 0
        mweb_total_block = 0.0

        for tx in block['tx']:
            for vout in tx.get('vout', []):
                type_ = vout.get('scriptPubKey', {}).get('type', '')
                value = vout.get('value', 0.0)

                if type_ == 'witness_mweb_hogaddr':
                    mweb_total_block += value
                elif type_ == 'witness_mweb_pegin':
                    batch_pegins.append((tx['txid'], height, value))
                    peg_in_count += 1

        mweb_total_running = mweb_total_block

        if height % COMMIT_EVERY_N_BLOCKS == 0 or height == tip:
            if batch_pegins:
                cursor.executemany('''
                    INSERT OR IGNORE INTO mweb_pegins (txid, block_height, amount)
                    VALUES (?, ?, ?)
                ''', batch_pegins)
                batch_pegins.clear()

            set_last_scanned_block(height)
            set_mweb_total(mweb_total_running)
            conn.commit()
            print(f"Committed at block {height} (Set MWEB total: {mweb_total_running:.4f})")

        print(f"Scanned block {height} | Peg-ins: {peg_in_count} | MWEB total in block: {mweb_total_block:.4f}")

def poll_for_blocks(interval=180):
    print(f"Polling for new blocks every {interval} seconds...")
    last_seen = rpc_request('getblockcount')

    while True:
        try:
            time.sleep(interval)
            current = rpc_request('getblockcount')
            if current > last_seen:
                print(f"New block(s) detected: {current}")
                scan_blocks()
                last_seen = current
            else:
                print("No new blocks yet.")
        except Exception as e:
            print(f"Polling error: {e}")
            time.sleep(5)

if __name__ == "__main__":
    try:
        scan_blocks()
        poll_for_blocks()
    except KeyboardInterrupt:
        print("Shutting down...")
    finally:
        conn.commit()
        conn.close()
