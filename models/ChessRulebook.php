<?php

class ChessRulebook {
	// Coordinates are in (rank, file) / (y, x) format
	const OCLOCK_OFFSETS = array(
		1 => array(2,1),
		2 => array(1,2),
		4 => array(-1,2),
		5 => array(-2,1),
		7 => array(-2,-1),
		8 => array(-1,-2),
		10 => array(1,-2),
		11 => array(2,-1)
	);
	const DIRECTION_OFFSETS = array(
		'north' => array(1,0),
		'south' => array(-1,0),
		'east' => array(0,1),
		'west' => array(0,-1),
		'northeast' => array(1,1),
		'northwest' => array(1,-1),
		'southeast' => array(-1,1),
		'southwest' => array(-1,-1)
	);
	
	const BISHOP_DIRECTIONS = array(
		'northwest',
		'northeast',
		'southwest',
		'southeast'
	);
	const ROOK_DIRECTIONS = array(
		'north',
		'south',
		'east',
		'west'
	);
	const QUEEN_DIRECTIONS = array(
		'north',
		'south',
		'east',
		'west',
		'northwest',
		'northeast',
		'southwest',
		'southeast'
	);
	const KING_DIRECTIONS = array(
		'north',
		'south',
		'east',
		'west',
		'northwest',
		'northeast',
		'southwest',
		'southeast'
	);
	const KNIGHT_DIRECTIONS = array(1, 2, 4, 5, 7, 8, 10, 11);
	const BLACK_PAWN_CAPTURE_DIRECTIONS = array('southeast', 'southwest');
	const BLACK_PAWN_MOVEMENT_DIRECTIONS = array('south');
	const WHITE_PAWN_CAPTURE_DIRECTIONS = array('northeast', 'northwest');
	const WHITE_PAWN_MOVEMENT_DIRECTIONS = array('north');
	
	const PROMOTION_PIECES = array(
		'queen',
		'rook',
		'bishop',
		'knight'
	);
	
	static function get_legal_moves_list(
		$color_to_move, // Color changes when we call recursively. Can't rely on $board for color.
		$board, // ChessBoard, not ChessBoard->board. We need the entire board in a couple of methods.
		$need_perfect_move_list = TRUE,
		$store_board_in_moves = TRUE,
		$need_perfect_notation = TRUE
	) {
		$pieces_to_check = self::get_all_pieces_by_color($color_to_move, $board);
		
		$moves = array();
		
		// TODO: Stalemate & Checkmate detection
		// if ( ! $legal_moves )
			// get threatened squares
			// if friendly king is threatened
				// checkmate
			// else
				// stalemate
		
		foreach ( $pieces_to_check as $key => $piece ) {
			if ( $piece->type == 'pawn' ) {
				if ( $piece->color == 'white' ) {
					if ( $piece->on_rank(2) ) {
						$moves = self::add_slide_moves_to_moves_list(self::WHITE_PAWN_MOVEMENT_DIRECTIONS, 2, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
					} else {
						$moves = self::add_slide_moves_to_moves_list(self::WHITE_PAWN_MOVEMENT_DIRECTIONS, 1, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
					}
					
					$moves = self::add_capture_moves_to_moves_list(self::WHITE_PAWN_CAPTURE_DIRECTIONS, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
					
					$moves = self::add_en_passant_moves_to_moves_list($piece, $board, $moves, $store_board_in_moves);
				} elseif ( $piece->color == 'black' ) {
					if ( $piece->on_rank(7) ) {
						$moves = self::add_slide_moves_to_moves_list(self::BLACK_PAWN_MOVEMENT_DIRECTIONS, 2, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
					} else {
						$moves = self::add_slide_moves_to_moves_list(self::BLACK_PAWN_MOVEMENT_DIRECTIONS, 1, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
					}
					
					$moves = self::add_capture_moves_to_moves_list(self::BLACK_PAWN_CAPTURE_DIRECTIONS, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
					
					$moves = self::add_en_passant_moves_to_moves_list($piece, $board, $moves, $store_board_in_moves);
				}
			} elseif ( $piece->type == 'knight' ) {
				$moves = self::add_jump_and_jumpcapture_moves_to_moves_list(self::KNIGHT_DIRECTIONS, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
			} elseif ( $piece->type == 'bishop' ) {
				$moves = self::add_slide_and_slidecapture_moves_to_moves_list(self::BISHOP_DIRECTIONS, 7, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
			} elseif ( $piece->type == 'rook' ) {
				$moves = self::add_slide_and_slidecapture_moves_to_moves_list(self::ROOK_DIRECTIONS, 7, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
			} elseif ( $piece->type == 'queen' ) {
				$moves = self::add_slide_and_slidecapture_moves_to_moves_list(self::QUEEN_DIRECTIONS, 7, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
			} elseif ( $piece->type == 'king' ) {
				$moves = self::add_slide_and_slidecapture_moves_to_moves_list(self::KING_DIRECTIONS, 1, $moves, $piece, $color_to_move, $board, $store_board_in_moves);
				
				// Set $king here so castling function can use it later.
				$king = $piece;
			}
		}
		
		if ( $need_perfect_move_list ) {
			$moves = self::eliminate_king_in_check_moves($king, $moves, $color_to_move);
			
			$moves = self::add_castling_moves_to_moves_list($moves, $king, $board);
		}
		
		if ( $need_perfect_notation ) {
			// TODO: Move notation - disambiguate vague starting squares
			// foreach $moves as $key => $move
			// if $move->piece->type == queen, rook, knight, bishop
			// $pieces_on_same_rank
			// $pieces_on_same_file
			// if ( $pieces_on_same_rank > 1 && $pieces_on_same_file > 1 )
			// $move->disambiguation = columnrow;
			// elseif ( $pieces_on_same_rank > 1 )
			// $move->disambiguation = row);
			// elseif ( $pieces_on_same_column > 1 )
			// $move->disambiguation = column;
			
			// if move puts enemy king in check, tell the $move object so it can add a + to the notation
			self::mark_checks_and_checkmates($moves, $color_to_move);
			
			// TODO: alphabetize
		}
		
		return $moves;
	}
	
	static function add_slide_and_slidecapture_moves_to_moves_list($directions_list, $spaces, $moves, $piece, $color_to_move, $board, $store_board_in_moves) {
		foreach ( $directions_list as $key => $direction ) {
			// $spaces should be 1 for king, 1 or 2 for pawns, 7 for all other sliding pieces
			// 7 is the max # of squares you can slide on a chessboard

			$legal_move_list = array();
			
			for ( $i = 1; $i <= $spaces; $i++ ) {
				$current_xy = self::DIRECTION_OFFSETS[$direction];
				$current_xy[0] *= $i;
				$current_xy[1] *= $i;
				
				$ending_square = self::square_exists_and_not_occupied_by_friendly_piece(
					$piece->square,
					$current_xy[0],
					$current_xy[1],
					$color_to_move,
					$board
				);
				
				if ( $ending_square ) {
					$capture = FALSE;
					
					if ( $board->board[$ending_square->rank][$ending_square->file] ) {
						if ( $board->board[$ending_square->rank][$ending_square->file]->color != $color_to_move ) {
							$capture = TRUE;
						}
					}
					
					array_push($legal_move_list, new ChessMove(
						$piece->square,
						$ending_square,
						$piece->color,
						$piece->type,
						$capture,
						$board,
						$store_board_in_moves
					));
					
					if ( $capture ) {
						// stop sliding
						break;
					} else {
						// empty square
						// continue sliding
						continue;
					}
				} else {
					// square does not exist, or square occupied by friendly piece
					// stop sliding
					break;
				}
			}
			
			foreach ( $legal_move_list as $key2 => $value2 ) {
				array_push($moves, $value2);
			}
		}
		
		return $moves;
	}
	
	static function add_capture_moves_to_moves_list($directions_list, $moves, $piece, $color_to_move, $board, $store_board_in_moves) {
		foreach ( $directions_list as $key => $direction ) {
			$legal_move_list = array();
			
			$current_xy = self::DIRECTION_OFFSETS[$direction];
			
			$ending_square = self::square_exists_and_not_occupied_by_friendly_piece(
				$piece->square,
				$current_xy[0],
				$current_xy[1],
				$color_to_move,
				$board
			);
			
			if ( $ending_square ) {
				$capture = FALSE;
				
				if ( $board->board[$ending_square->rank][$ending_square->file] ) {
					if ( $board->board[$ending_square->rank][$ending_square->file]->color != $color_to_move ) {
						$capture = TRUE;
					}
				}
				
				if ( $capture ) {
					$move = new ChessMove(
						$piece->square,
						$ending_square,
						$piece->color,
						$piece->type,
						$capture,
						$board,
						$store_board_in_moves
					);
					
					// pawn promotion
					$white_pawn_capturing_on_rank_8 = $piece->type == "pawn" && $ending_square->rank == 8 && $piece->color == "white";
					$black_pawn_capturing_on_rank_1 = $piece->type == "pawn" && $ending_square->rank == 1 && $piece->color == "black";
					if (
						$white_pawn_capturing_on_rank_8 || $black_pawn_capturing_on_rank_1
					) {
						foreach ( self::PROMOTION_PIECES as $key => $type ) {
							$move2 = clone $move;
							$move2->set_promotion_piece($type);
							array_push($legal_move_list, $move2);
						}
					} else {
						array_push($legal_move_list, $move);
					}
				}
			}
			
			foreach ( $legal_move_list as $key2 => $value2 ) {
				array_push($moves, $value2);
			}
		}
		
		return $moves;
	}
	
	static function add_slide_moves_to_moves_list($directions_list, $spaces, $moves, $piece, $color_to_move, $board, $store_board_in_moves) {
		foreach ( $directions_list as $key => $direction ) {
			// $spaces should be 1 for king, 1 or 2 for pawns, 7 for all other sliding pieces
			// 7 is the max # of squares you can slide on a chessboard

			$legal_move_list = array();
			
			for ( $i = 1; $i <= $spaces; $i++ ) {
				$current_xy = self::DIRECTION_OFFSETS[$direction];
				$current_xy[0] *= $i;
				$current_xy[1] *= $i;
				
				$ending_square = self::square_exists_and_not_occupied_by_friendly_piece(
					$piece->square,
					$current_xy[0],
					$current_xy[1],
					$color_to_move,
					$board
				);
				
				if ( $ending_square ) {
					$capture = FALSE;
					
					if ( $board->board[$ending_square->rank][$ending_square->file] ) {
						if ( $board->board[$ending_square->rank][$ending_square->file]->color != $color_to_move ) {
							$capture = TRUE;
						}
					}
					
					if ( $capture ) {
						// enemy piece in square
						// stop sliding
						break;
					} else {
						$new_move = new ChessMove(
							$piece->square,
							$ending_square,
							$piece->color,
							$piece->type,
							$capture,
							$board,
							$store_board_in_moves
						);
						
						// en passant target square
						if (
							$piece->type == 'pawn' &&
							$i == 2
						) {
							$en_passant_xy = self::DIRECTION_OFFSETS[$direction];
							$en_passant_xy[0] *= 1;
							$en_passant_xy[1] *= 1;
							
							$en_passant_target_square = self::square_exists_and_not_occupied_by_friendly_piece(
								$piece->square,
								$en_passant_xy[0],
								$en_passant_xy[1],
								$color_to_move,
								$board
							);
							
							if ($store_board_in_moves ) {
								$new_move->board->en_passant_target_square = $en_passant_target_square;
							}
						}
						
						// pawn promotion
						$white_pawn_moving_to_rank_8 = $piece->type == "pawn" && $ending_square->rank == 8 && $piece->color == "white";
						$black_pawn_moving_to_rank_1 = $piece->type == "pawn" && $ending_square->rank == 1 && $piece->color == "black";
						if (
							$white_pawn_moving_to_rank_8 || $black_pawn_moving_to_rank_1
						) {
							foreach ( self::PROMOTION_PIECES as $key => $type ) {
								$move2 = clone $new_move;
								$move2->set_promotion_piece($type);
								array_push($legal_move_list, $move2);
							}
						} else {
							array_push($legal_move_list, $new_move);
						}
						
						// empty square
						// continue sliding
						continue;
					}
				} else {
					// square does not exist, or square occupied by friendly piece
					// stop sliding
					break;
				}
			}
			
			foreach ( $legal_move_list as $key2 => $value2 ) {
				array_push($moves, $value2);
			}
		}
		
		return $moves;
	}
	
	static function add_jump_and_jumpcapture_moves_to_moves_list($oclock_list, $moves, $piece, $color_to_move, $board, $store_board_in_moves) {
		foreach ( $oclock_list as $key => $oclock ) {
			$ending_square = self::square_exists_and_not_occupied_by_friendly_piece(
				$piece->square,
				self::OCLOCK_OFFSETS[$oclock][0],
				self::OCLOCK_OFFSETS[$oclock][1],
				$color_to_move,
				$board
			);
			
			$legal_move_list = array();
			
			if ( $ending_square ) {
				$capture = FALSE;
				
				if ( $board->board[$ending_square->rank][$ending_square->file] ) {
					// enemy piece
					if ( $board->board[$ending_square->rank][$ending_square->file]->color != $color_to_move ) {
						$capture = TRUE;
					}
				}
				
				array_push($legal_move_list, new ChessMove(
					$piece->square,
					$ending_square,
					$piece->color,
					$piece->type,
					$capture,
					$board,
					$store_board_in_moves
				));
			}
			
			foreach ( $legal_move_list as $key2 => $value2 ) {
				array_push($moves, $value2);
			}
		}
		
		return $moves;
	}
	
	
	static function add_en_passant_moves_to_moves_list($piece, $board, $moves, $store_board_in_moves) {
		// TODO: Move these to constants called EN_PASSANT_RULES_WHITE and EN_PASSANT_RULES_BLACK
		if ( $piece->color == 'white' ) {
			$capture_directions_from_starting_square = array('northeast', 'northwest');
			$enemy_pawn_direction_from_ending_square = array('south');
			$en_passant_rank = 5;
		} elseif ( $piece->color == 'black' ) {
			$capture_directions_from_starting_square = array('southeast', 'southwest');
			$enemy_pawn_direction_from_ending_square = array('north');
			$en_passant_rank = 4;
		}
		
		if ( $piece->on_rank($en_passant_rank) && $board->en_passant_target_square ) {
			$squares_to_check = self::get_squares_in_these_directions($piece->square, $capture_directions_from_starting_square, 1);
			foreach ( $squares_to_check as $key => $square ) {
				if ( $square->alphanumeric == $board->en_passant_target_square->alphanumeric ) {
					$move = new ChessMove(
						$piece->square,
						$square,
						$piece->color,
						$piece->type,
						TRUE,
						$board,
						$store_board_in_moves
					);
					$move->en_passant = TRUE;
					if ( $store_board_in_moves ) {
						$enemy_pawn_square = self::get_squares_in_these_directions($square, $enemy_pawn_direction_from_ending_square, 1);
						$move->board->remove_piece_from_square($enemy_pawn_square[0]);
					}
					array_push($moves, $move);
				}
			}
		}
		
		return $moves;
	}

	static function add_castling_moves_to_moves_list($moves, $piece, $board) {
		// This can't be a constant or a class variable because it has ChessSquares in it.
		// I tried using strings instead of ChessSquares, but it breaks stuff.
		// Not worth the trouble.
		$castling_rules = array (
			array(
				'boolean_to_check' => 'white_can_castle_kingside',
				'color_to_move' => 'white',
				'rook_start_square' => new ChessSquare('h1'),
				'king_end_square' => new ChessSquare('g1'),
				'cannot_be_attacked' => array(
					new ChessSquare('e1'),
					new ChessSquare('f1'),
					new ChessSquare('g1')
				),
				'cannot_be_occupied' => array(
					new ChessSquare('f1'),
					new ChessSquare('g1')
				)
			),
			array(
				'boolean_to_check' => 'white_can_castle_queenside',
				'color_to_move' => 'white',
				'rook_start_square' => new ChessSquare('a1'),
				'king_end_square' => new ChessSquare('c1'),
				'cannot_be_attacked' => array(
					new ChessSquare('e1'),
					new ChessSquare('d1'),
					new ChessSquare('c1')
				),
				'cannot_be_occupied' => array(
					new ChessSquare('d1'),
					new ChessSquare('c1'),
					new ChessSquare('b1')
				)
			),
			array(
				'boolean_to_check' => 'black_can_castle_kingside',
				'color_to_move' => 'black',
				'rook_start_square' => new ChessSquare('h8'),
				'king_end_square' => new ChessSquare('g8'),
				'cannot_be_attacked' => array(
					new ChessSquare('e8'),
					new ChessSquare('f8'),
					new ChessSquare('g8')
				),
				'cannot_be_occupied' => array(
					new ChessSquare('f8'),
					new ChessSquare('g8')
				)
			),
			array(
				'boolean_to_check' => 'black_can_castle_queenside',
				'color_to_move' => 'black',
				'rook_start_square' => new ChessSquare('a8'),
				'king_end_square' => new ChessSquare('c8'),
				'cannot_be_attacked' => array(
					new ChessSquare('e8'),
					new ChessSquare('d8'),
					new ChessSquare('c8')
				),
				'cannot_be_occupied' => array(
					new ChessSquare('d8'),
					new ChessSquare('c8'),
					new ChessSquare('b8')
				)
			),
		);
		
		$legal_move_list = array();
		
		foreach ( $castling_rules as $key => $value ) {
			// only check castling for current color_to_move
			if ( $value['color_to_move'] != $board->color_to_move ) {
				continue;
			}
			
			// make sure the FEN has castling permissions
			$boolean_to_check = $value['boolean_to_check'];
			if ( ! $board->castling[$boolean_to_check] ) {
				continue;
			}
			
			// check all cannot_be_attacked squares
			$enemy_color = self::invert_color($board->color_to_move);
			$squares_attacked_by_enemy = self::get_squares_attacked_by_this_color($enemy_color, $board);
			foreach ( $value['cannot_be_attacked'] as $key2 => $square_to_check ) {
				if ( in_array($square_to_check->alphanumeric, $squares_attacked_by_enemy) ) {
					continue 2;
				}
			}
			
			// check all cannot_be_occupied_squares
			foreach ( $value['cannot_be_occupied'] as $key2 => $square_to_check ) {
				if ( $board->square_is_occupied($square_to_check) ) {
					continue 2;
				}
			}
			
			// Make sure the rook is still there. This case should only occur in damaged FENs. If the rook isn't there, throw an invalid FEN exception (to prevent a clone error later on).
			$rook_start_square = $value['rook_start_square'];
			$rank = $rook_start_square->rank;
			$file = $rook_start_square->file;
			$piece_to_check = $board->board[$rank][$file];
			if ( ! $piece_to_check ) {
				throw new Exception('ChessGame Class - Invalid FEN - Castling permissions set to TRUE but rook is missing');
			}
			if (
				$piece_to_check->type != 'rook' ||
				$piece_to_check->color != $board->color_to_move
			) {
				throw new Exception('ChessGame Class - Invalid FEN - Castling permissions set to TRUE but rook is missing');
			}
			
			// The ChessMove class handles displaying castling notation, taking castling privileges out of the FEN, and moving the rook into the right place on the board. No need to do anything extra here.
			array_push($legal_move_list, new ChessMove(
				$piece->square,
				$value['king_end_square'],
				$piece->color,
				$piece->type,
				FALSE,
				$board
			));
		}
		
		foreach ( $legal_move_list as $key2 => $value2 ) {
			array_push($moves, $value2);
		}
		
		return $moves;
	}
	
	static function mark_checks_and_checkmates($moves, $color_to_move) {
		$enemy_color = self::invert_color($color_to_move);
		
		foreach ( $moves as $key => $move ) {
			$enemy_king_square = $move->board->get_king_square($enemy_color);
			
			$squares_attacked_by_moving_side = self::get_squares_attacked_by_this_color($color_to_move, $move->board);
			
			if ( in_array($enemy_king_square->alphanumeric, $squares_attacked_by_moving_side) ) {
				$move->check = TRUE;
				
				$legal_moves_for_enemy = self::get_legal_moves_list($enemy_color, $move->board, TRUE, TRUE, FALSE);
				
				if ( ! $legal_moves_for_enemy ) {
					$move->checkmate = TRUE;
				}
			}
		}
	}
	
	static function eliminate_king_in_check_moves($king, $moves, $color_to_move) {
		$enemy_color = self::invert_color($color_to_move);
		
		$new_moves = array();
		if ( ! $king ) {
			throw new Exception('ChessGame Class - Invalid FEN - One of the kings is missing');
		}
		foreach ( $moves as $key => $move ) {
			$friendly_king_square = $move->board->get_king_square($color_to_move);
			
			$squares_attacked_by_enemy = self::get_squares_attacked_by_this_color($enemy_color, $move->board);
			
			if ( ! in_array($friendly_king_square->alphanumeric, $squares_attacked_by_enemy) ) {
				array_push($new_moves, $move);
			}
		}
		
		return $new_moves;
	}
		
	static function get_all_pieces_by_color($color_to_move, $board) {
		$list_of_pieces = array();
		
		for ( $i = 1; $i <= 8; $i++ ) {
			for ( $j = 1; $j <=8; $j++ ) {
				$piece = $board->board[$i][$j];
				
				if ( $piece ) {
					if ( $piece->color == $color_to_move ) {
						array_push($list_of_pieces, $piece);
					}
				}
			}
		}
		
		return $list_of_pieces;
	}
	
	// positive X = east, negative X = west, positive Y = north, negative Y = south
	static function square_exists_and_not_occupied_by_friendly_piece($starting_square, $x_delta, $y_delta, $color_to_move, $board) {
		$rank = $starting_square->rank + $x_delta;
		$file = $starting_square->file + $y_delta;
		
		$ending_square = self::try_to_make_square_using_rank_and_file_num($rank, $file);
		
		// Ending square is off the board
		if ( ! $ending_square ) {
			return FALSE;
		}
		
		// Ending square contains a friendly piece
		if ( $board->board[$rank][$file] ) {
			if ( $board->board[$rank][$file]->color == $color_to_move ) {
				return FALSE;
			}
		}
		
		return $ending_square;
	}
	
	static function try_to_make_square_using_rank_and_file_num($rank, $file) {
		if ( $rank >= 1 && $rank <= 8 && $file >= 1 && $file <= 8 ) {
			return new ChessSquare($rank, $file);
		} else {
			return FALSE;
		}
	}
	
	static function invert_color($color) {
		if ( $color == 'white' ) {
			return 'black';
		} else {
			return 'white';
		}
	}
	
	static function get_squares_attacked_by_this_color($color, $board) {
		$legal_moves_for_opponent = self::get_legal_moves_list($color, $board, FALSE, FALSE, FALSE);
		
		$squares_attacked = array();
		foreach ( $legal_moves_for_opponent as $key => $move ) {
			// avoid duplicates
			if ( ! in_array($move->ending_square->alphanumeric, $squares_attacked) ) {
				array_push($squares_attacked, $move->ending_square->alphanumeric);
			}
		}
		
		return $squares_attacked;
	}
	
	// Used to generate en passant squares.
	static function get_squares_in_these_directions($starting_square, $directions_list, $spaces) {
		$list_of_squares = array();
		
		foreach ( $directions_list as $key => $direction ) {
			// $spaces should be 1 for king, 1 or 2 for pawns, 7 for all other sliding pieces
			// 7 is the max # of squares you can slide on a chessboard
			
			$current_xy = self::DIRECTION_OFFSETS[$direction];
			$current_xy[0] =  $current_xy[0] * $spaces + $starting_square->rank;
			$current_xy[1] =  $current_xy[1] * $spaces + $starting_square->file;
			
			$square = self::try_to_make_square_using_rank_and_file_num($current_xy[0], $current_xy[1]);
			
			if ( $square ) {
				array_push($list_of_squares, $square);
			}
		}
		
		return $list_of_squares;
	}
}