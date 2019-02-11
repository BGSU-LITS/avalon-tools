<?php
/**
 * Cue Get Action Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * An class for the index action.
 */
class CueGetAction extends AbstractAction
{
    /**
     * Method called when class is invoked as an action.
     * @param Request $req The request for the action.
     * @param Response $res The response from the action.
     * @param array $args The arguments for the action.
     * @return Response The response from the action.
     */
    public function __invoke(Request $req, Response $res, array $args)
    {
        // Unused.
        $req;

        // Check if a cue file hasn't been uploaded.
        if (empty($this->session->avalon_cue['files'])) {
            // Add flash message with error.
            $this->flash->addMessage(
                'danger',
                'A cue file has not been uploaded.'
            );

            // Redirect to the homepage on error.
            return $res->withRedirect($req->getUri()->getBasePath());
        }

        // Get the metadata from the session after adding end times.
        $args['meta'] = $this->addEndTimes($this->getMeta());

        // Get the email address of the user from the session.
        $args['email'] = $this->session->avalon_cue['email'];

        // Render form template.
        return $this->view->render($res, 'cue.html.twig', $args);
    }

    /**
     * Get the metadata from the session in a format for templating.
     * @return array The metadata from the session.
     */
    private function getMeta()
    {
        // Start with no metadata.
        $meta = [];

        // Loop through each cue file in the session.
        foreach ($this->session->avalon_cue['files'] as $cue) {
            // Loop through each track in the cue file.
            foreach ($cue['tracks'] as $track) {
                // Make sure the track is for an audio file.
                if (empty($track['datafile']['filename'])) {
                    continue;
                }

                // Get the file name of the audio file for the track.
                $file = $track['datafile']['filename'];

                // Check if the metadata isn't set for the audio file.
                if (empty($meta[$file])) {
                    // Add the title and performer of the audio file.
                    $meta[$file] = [
                        'label' => $cue['title'],
                        'performer' => $cue['performer']
                    ];

                    // Add the date and genre of the audio file.
                    foreach (['date', 'genre'] as $key) {
                        if (!empty($cue['comments'][$key])) {
                            $meta[$file][$key] = implode(
                                ', ',
                                $cue['comments'][$key]
                            );
                        }
                    }
                }

                // Use the title of the track as the label.
                $label = $track['title'];

                // If the track has a separate performer from the whole album,
                // add that information to the label.
                if ($track['performer']) {
                    if ($track['performer'] !== $cue['performer']) {
                        $label .= ' (' . $track['performer'] . ')';
                    }
                }

                // Add the label and begin time of the track to the metadata of
                // all tracks from the audio file.
                $meta[$file]['tracks'][] = [
                    'label' => $label,
                    'begin' => sprintf(
                        '%02d:%02d.%02d',
                        $track['index'][1]['minutes'],
                        $track['index'][1]['seconds'],
                        ($track['index'][1]['frames'] / 75) * 100
                    )
                ];
            }
        }

        return $meta;
    }

    /**
     * Add the end time of each track as the begin time of the next track.
     * @param array $meta The metadata from the getMeta() method.
     * @return array The metadata with end times added to tracks except last.
     */
    private function addEndTimes($meta)
    {
        // Loop through each file in the metadata.
        foreach (array_keys($meta) as $file) {
            // Start without a next track begin time, as the last track doesn't
            // need an end time for Avalon to display its length correctly.
            $next = false;

            // Loop through all tracks in reverse order.
            $tracks = array_reverse(array_keys($meta[$file]['tracks']));

            foreach ($tracks as $track) {
                // If a next track begin time is available, use it as the end
                // time for the current track.
                if (!empty($next)) {
                    $meta[$file]['tracks'][$track]['end'] = $next;
                }

                // Set the current track's begin time as next.
                $next = $meta[$file]['tracks'][$track]['begin'];
            }
        }

        return $meta;
    }
}
