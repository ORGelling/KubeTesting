import { FormEvent, useEffect, useState } from 'react';

type MediaFile = {
  id: number;
  original_name: string;
  mime_type: string | null;
  size_bytes: number;
  status: 'pending' | 'complete';
};

export default function Files() {
  const [files, setFiles] = useState<MediaFile[]>([]);
  const [uploading, setUploading] = useState(false);
  const [message, setMessage] = useState('');

  const csrfToken =
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
      ?.content ?? '';

  async function loadFiles() {
    const response = await fetch('/api/files', {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
      },
    });

    if (!response.ok) {
      setMessage('Could not load files.');
      return;
    }

    setFiles(await response.json());
  }

  useEffect(() => {
    void loadFiles();
  }, []);

  async function upload(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const form = event.currentTarget;
    const formData = new FormData(form);

    setUploading(true);
    setMessage('');

    try {
      const response = await fetch('/api/files', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: formData,
      });

      if (!response.ok) {
        const body = await response.json();
        throw new Error(body.message ?? 'Upload failed.');
      }

      form.reset();
      setMessage('Uploaded. Refresh the list in a moment to see complete.');
      await loadFiles();
    } catch (error) {
      setMessage(
        error instanceof Error ? error.message : 'Upload failed.',
      );
    } finally {
      setUploading(false);
    }
  }

  return (
    <main>
      <h1>My Files</h1>

      <form onSubmit={upload}>
        <input name="file" required type="file" />
        <button disabled={uploading} type="submit">
          {uploading ? 'Uploading…' : 'Upload file'}
        </button>
      </form>

      <button onClick={loadFiles} type="button">
        Refresh list
      </button>

      {message && <p>{message}</p>}

      <ul>
        {files.map((file) => (
          <li key={file.id}>
            <strong>{file.original_name}</strong>
            <span> — {file.status}</span>

            {file.status === 'complete' && (
              <>
                {' '}
                <a href={`/api/files/${file.id}/download`}>Download</a>
              </>
            )}
          </li>
        ))}
      </ul>
    </main>
  );
}
